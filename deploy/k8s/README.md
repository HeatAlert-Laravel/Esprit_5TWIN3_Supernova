# HeatAlert on the existing single-node Kubernetes cluster

These manifests prepare a **future** Kubernetes deployment. They are not part of the active Docker Compose service and must not be applied during preparation. The cluster is already installed on Ubuntu 24.04 with Kubernetes v1.37.2, a dedicated containerd 2.4.2 CRI socket at `/run/containerd-k8s/containerd.sock`, Flannel v0.28.10 (`10.244.0.0/16`) and Service CIDR `10.96.0.0/12`. The one node, `heatalert-single-node`, carries both control-plane and worker roles. The public tunnel, Jenkins, monitoring services and Compose port 8088 remain unchanged.

## Manifests and wiring

| File | Resource | Purpose |
| --- | --- | --- |
| `00-namespace.yaml` | Namespace | `heatalert` isolation |
| `01-storageclass.yaml` | StorageClass | Static local PVs, `WaitForFirstConsumer`, `Retain` |
| `02-local-volumes.yaml` | 3 PVs + 2 PVCs | MySQL, public avatars, private audio |
| `03-mysql-service.yaml` | Headless Service | Internal `mysql:3306` for the StatefulSet and Laravel |
| `04-mysql-statefulset.yaml` | StatefulSet + claim template | One MySQL 8.4 instance, its own local data directory |
| `05-laravel-service.yaml` | ClusterIP Service | Internal `app:9000`, matching the existing Nginx config |
| `06-laravel-deployment.yaml` | Deployment | One non-root PHP-FPM Pod; database sessions and file cache |
| `07-nginx-service.yaml` | NodePort Service | VM port 30088, separate from Compose port 8088 |
| `08-nginx-deployment.yaml` | Deployment | One Nginx Pod with compiled Vite assets and read-only public files |
| `09-local-logs.yaml` | PV + PVC | Persistent Laravel log directory |
| `kustomization.yaml` | Resource list | Local rendering and structural inspection with `kubectl kustomize` |

The current images already contain `/var/www/html/public/build` and the same public paths. Nginx uses `fastcgi_pass app:9000`, so the Kubernetes Service is deliberately named `app`. The public PVC is mounted at `/var/www/html/storage/app/public` in PHP and `/var/www/html/public/storage` in Nginx. Laravel serves private audio from `storage/app/private` through its controller; Nginx never mounts that path. `storage/framework/cache` and `storage/framework/views` use disposable `emptyDir` volumes. Sessions use MySQL. The `Recreate` strategy avoids simultaneous writers during an application image change on this single node.

Requests total 400m CPU and 832Mi RAM; limits total 2 CPU and 2816Mi RAM across MySQL, PHP-FPM and Nginx. This leaves headroom for the control plane, Jenkins, Docker Compose and monitoring on the 4-vCPU / 6.2-GiB VM. Observe actual memory and disk use before enabling the Pods. No Redis, queue worker, ingress controller or new monitoring stack is added.

PHP-FPM runs permanently as UID/GID 33 with privilege escalation and Linux capabilities disabled. Service-account tokens are not mounted into any application Pod. The existing MySQL image retains its default entrypoint identity for first-time data-directory initialization; the existing Nginx image retains its master process behavior for port 80. There are no privileged init containers or `hostNetwork` Pods.

## Local storage and node preparation

The four paths on the Kubernetes host are **not** Docker Compose volumes. Before future activation, verify the exact node and inspect available space, then create only missing directories:

```bash
kubectl get node heatalert-single-node -o wide
df -h /srv /var/lib/containerd
sudo install -d -m 0700 /srv/heatalert-k8s/mysql-data
sudo install -d -m 0755 -o 33 -g 33 /srv/heatalert-k8s/public-files
sudo install -d -m 0700 -o 33 -g 33 /srv/heatalert-k8s/private-files
sudo install -d -m 0770 -o 33 -g 33 /srv/heatalert-k8s/app-logs
```

The MySQL image initializes its own data directory and adjusts its ownership during first startup; verify the image's runtime UID and host permissions before activation. Do not copy Docker's live MySQL files into this path or mount one data directory into both runtimes. The public directory must remain readable by Nginx's worker and writable by PHP UID 33; private files must remain inaccessible to Nginx. The PV capacities (8 + 2 + 4 + 1 GiB) are **scheduling declarations, not filesystem quotas**. They do not reserve or enforce 15 GiB; keep free disk space for images, etcd, logs and backups. All PVs use `Retain`. Their selectors bind each PVC to the intended local PV. `WaitForFirstConsumer` delays binding until a Pod is scheduled; manifests use `nodeSelector`, never `nodeName`, so the scheduler can honor the PV node affinity.

## Secrets and image tags

Read [SECRETS.md](SECRETS.md). Secrets are created manually from `/opt/heatalert/config/production.env` on Ubuntu. The existing `APP_KEY` is reused; the script never generates a new one. `K8S_APP_URL` supplies the future NodePort or HTTPS URL without changing Compose's current `APP_URL`. MySQL receives only database credentials. The Laravel Secret receives its application, SMTP and optional AI settings but not the MySQL root password.

The checked-in Deployment image tags are deliberately unusable placeholders. After a full commit has passed tests and its images are pushed, render a disposable manifest directory:

```bash
export DOCKERHUB_NAMESPACE=aymen74407
export IMAGE_TAG=<tested-40-character-Git-SHA>
render_dir="$(deploy/scripts/k8s-render.sh)"
grep -R 'image:' "$render_dir"
```

`k8s-render.sh` validates the SHA and namespace, substitutes both image tags, and only prints the path of the rendered directory. It does **not** contact or modify Kubernetes. Rendered files live under `/tmp`, outside Git. Never deploy `latest` or an image built from a different commit.

`kubectl kustomize deploy/k8s` renders the source files locally for inspection; it does not deploy them. The source files still contain image placeholders, so use `k8s-render.sh` for a future deployment candidate.

## Future activation sequence — manual change window only

1. Take a verified MySQL dump and separate copies of public/private files. Prepare a documented cutover and rollback window. The running Compose MySQL must remain authoritative until the migration is explicitly approved. A blank Kubernetes MySQL is not a copy of production.
2. Verify host directories, PV capacity, node labels, current cluster context and available resources. Confirm the existing `heatalert-local` StorageClass matches this file before applying it; immutable changes require an administrator decision.
3. Create Kubernetes Secrets as in [SECRETS.md](SECRETS.md). Do not expose their YAML or decoded data in terminal logs.
4. Render manifests for the tested image SHA. Inspect `kubectl diff -f "$render_dir"` with care because a diff involving Secrets can reveal data; Secrets are managed separately and are absent from rendered manifests. Apply the numbered manifests only inside the change window, after explicit approval.
5. Restore the verified database dump into the **separate** Kubernetes MySQL volume, run `php artisan migrate --force` only after reviewing outstanding migrations, and verify `/up`, the homepage, CSS, login, avatars and private audio on `http://<VM-IP>:30088`. Do not route the existing public tunnel to NodePort until the application and data have been verified.
6. Record the previous image SHA and preserve Compose volumes. An application image rollback does not automatically reverse database migrations; see [ROLLBACK.md](../../docs/devops/ROLLBACK.md).

Operational commands after an approved activation:

```bash
kubectl -n heatalert get pods,svc,pvc
kubectl get pv
kubectl -n heatalert rollout status statefulset/mysql
kubectl -n heatalert rollout status deployment/app
kubectl -n heatalert rollout status deployment/nginx
kubectl -n heatalert logs deployment/app --tail=100
curl -f http://127.0.0.1:30088/up
```

The manifest renderer does not deploy application resources. Secret creation is a separate, explicitly guarded operation. Do not use `kubectl delete pvc`, `kubectl delete pv`, `docker compose down -v` or volume pruning as a rollback mechanism.

## Future Jenkins integration

The active UI-defined Jenkins pipeline remains `GitHub → Tests → Docker Build → Docker Hub → Docker Compose`. A later, separately reviewed Pipeline script may add SonarQube and its Quality Gate, then render these manifests with the full tested Git SHA and deploy to Kubernetes. Jenkins should use a dedicated kubeconfig or short-lived token held in Jenkins Credentials, scoped by a namespaced Role to only the required resources. Do not grant Jenkins a control-plane administrator kubeconfig or print tokens in logs. The cutover must also define database migration and rollback handling; image rollout alone is insufficient.

## Safe synchronization of the Ubuntu checkout

The Ubuntu checkout already has a local branch with untracked Kubernetes files. Save them **before** checking out or pulling this branch. The safest review path leaves that checkout untouched:

```bash
cd /path/to/existing/checkout
backup_dir="$HOME/heatalert-k8s-backup-$(date -u +%Y%m%dT%H%M%SZ)"
mkdir -m 700 "$backup_dir"
cp -a deploy/k8s "$backup_dir/k8s-local"
diff -qr deploy/k8s "$backup_dir/k8s-local"
git status --short
git clone --branch devops/kubernetes-single-node \
  https://github.com/HeatAlert-Laravel/Esprit_5TWIN3_Supernova.git \
  "$backup_dir/review-clone"
diff -ru "$backup_dir/k8s-local" "$backup_dir/review-clone/deploy/k8s" || true
```

Review and merge any useful local differences manually. Never run `git clean`, `git reset --hard`, or a checkout that overwrites untracked files. Once the local work has been reconciled, fetch the branch in the desired checkout and use `git pull --ff-only` only when Git reports no conflicting local changes.

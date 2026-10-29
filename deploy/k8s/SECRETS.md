# Kubernetes Secret handling

No Secret manifest or value is committed. The future Kubernetes instance uses the same existing Laravel `APP_KEY` and the currently configured SMTP credentials. On the Ubuntu VM, `/opt/heatalert/config/production.env` remains the source of private values; do not copy it into this repository, a Jenkins workspace, a rendered manifest directory or a report.

After confirming the intended `kubectl` context, the target node and the change window, create or update two Secrets explicitly:

```bash
export K8S_CONTEXT=<verified-context-name>
export K8S_APP_URL=http://<VM-IP>:30088
export K8S_SECRETS_CONFIRM=1
export PRODUCTION_ENV_FILE=/opt/heatalert/config/production.env
deploy/scripts/k8s-create-secrets.sh
unset K8S_SECRETS_CONFIRM
```

The script parses `KEY=VALUE` lines as data, never sources the file into a shell, never prints values and creates mode-600 temporary files that are removed on exit. It removes surrounding simple quotes when present. It overrides only `APP_URL`, `DB_HOST=mysql`, `DB_PORT=3306` and `DB_CONNECTION=mysql` for Kubernetes. The app Secret excludes `MYSQL_ROOT_PASSWORD`; the MySQL Secret contains only `DB_USERNAME`, `DB_PASSWORD` and `MYSQL_ROOT_PASSWORD`. Keep values literal in the private env file; resolve `${...}` references there before using this script. `K8S_APP_URL` may later be an HTTPS URL after the public reverse proxy is deliberately switched. Do not regenerate `APP_KEY` during this process.

The script requires an explicit context, confirmation flag and node/namespace checks. It sends Secret data through a local pipe to server-side apply and suppresses normal kubectl output. Do not enable shell tracing, run `kubectl get secret -o yaml`, use `kubectl describe pod` in public logs, or save a decoded Secret. Environment variables in Pods can still be read by users with sufficient Kubernetes privileges. Grant access to the `heatalert` namespace and its Secrets only to operators who need it; keep kubeconfig and etcd backups private.

Kubernetes Secrets are base64-encoded API objects, not automatically encrypted at rest. The cluster administrator must check whether API-server encryption at rest is configured for `secrets`, enable it if needed, and protect the encryption key and etcd backups. See the official [Kubernetes encryption-at-rest guide](https://kubernetes.io/docs/tasks/administer-cluster/encrypt-data/) and [Secret security guidance](https://kubernetes.io/docs/concepts/configuration/secret/). This task does not alter the cluster API server or etcd.

For future Jenkins automation, use a dedicated namespaced identity with narrowly scoped RBAC and a short-lived credential kept in Jenkins Credentials. The active Jenkins Pipeline script is unchanged. Secret updates should be a separate controlled operation; a changed environment Secret does not update already running Pods until they are restarted through a reviewed rollout.

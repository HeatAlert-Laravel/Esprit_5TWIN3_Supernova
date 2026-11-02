# Tunis locations and a classroom AI demonstration

The four place identities and Google Maps pins below were checked on **2 November 2026**. A map pin is approximate: it does not locate an accessible entrance or a drinking-water outlet. None of these records is certified as an operational cooling point by this change.

## Real-place records

```powershell
php artisan migrate
php artisan db:seed --class=TunisCoolingPointSeeder
```

This adds missing types/neighborhoods and four **inactive** real-place candidates. Open **Admin → Cooling points** to review their names, addresses, coordinates, sources and access restrictions. Drinking water, a shaded resting area and the complete accessible route remain unconfirmed, so their three flags start false. Existing records and later administrator edits are preserved on repeat runs. The seeder runs without internet access and is not called by the general `DatabaseSeeder`.

| Real place | Map latitude, longitude | Schedule recorded from sources |
| --- | --- | --- |
| Parc du Belvédère | 36.8219921, 10.1717264 | Tue–Fri and Sun 09:00–17:00; Sat 09:00–18:00; Mon closed |
| Jardin Habib Thameur | 36.8049593, 10.1777092 | Google Maps lists 24/7; confirm gate access |
| Bibliothèque nationale de Tunisie | 36.7996151, 10.1636260 | Official reception: Mon–Sat 08:30–20:00; individual rooms differ |
| Tunisia Mall | 36.8480222, 10.2790152 | Google Maps / website weekly table: daily 08:00–21:00; website FAQ differs |

The library can require identification, subscription or authorization. The mall is commercial; free potable water and permission to rest without buying something were not established. The assistant deliberately excludes weekday-specific schedule strings. Do not replace real schedules with `24/7` to force a match.

## Full AI demo in a private local database

For a classroom demonstration before on-site checks are possible, use the explicit simulation seeder. It retains the four real candidates and adds four separate records named **`DEMO - … (simulated amenities)`** at the researched map pins. Their addresses also begin **`DEMO ONLY`**. Water, a shaded resting area, accessibility and `24/7` opening are test assumptions for these demo records. No simulated flag is written onto a real-place candidate.

With `APP_ENV=local` in your ignored `.env`, run:

```powershell
php artisan config:clear
php artisan migrate
php artisan db:seed --class=TunisCoolingPointDemoSeeder
```

Both seeders preserve existing rows, so check any previously edited `DEMO -` records in Admin if you need to restore their test settings. The simulation seeder refuses production/staging environments. Keep this database private and deactivate the `DEMO -` records after the presentation.

Set `HEAT_ASSISTANT_GROQ_API_KEY` (or `GROQ_API_KEY`) locally, then run:

```powershell
php artisan config:clear
npm ci
npm run build
php artisan serve
```

Open `http://127.0.0.1:8000/cooling-points`, select English and **clear GPS** to use the address you say or type.

1. Type or say: **“I am near Avenue de Paris and Jardin Habib Thameur in Tunis. Please find drinking water and shade.”**
2. Confirm the street in central Tunis when the address choices appear.
3. Expect a clearly labelled demo result, a straight-line distance, a map and optional speech playback. From this origin, the simulated garden point should be nearest; the exact result depends on the origin you confirm.
4. Repeat with **“I am on Rue de l’Île de Zembretta, Les Berges du Lac II, Tunis. Please find drinking water and shade.”** Confirm a Lac II match. The simulated Tunisia Mall point should be nearest.
5. Tick wheelchair access and repeat. Demo fixtures are accessible by assumption so you can exercise that filter.

Tell the professor: **“These are real place names and map locations. The DEMO records simulate verified amenities and schedules to demonstrate the AI pipeline; operational verification is still pending.”**

Seed data works while the web server is stopped. A live demonstration still requires Laravel, the database, Groq and (for address lookup) Photon to be available. No live Groq or microphone recognition was validated by the automated provider mocks. See [the assistant guide](module3-heat-distress-assistant.md) for limits and troubleshooting.

## Source trail

Google Maps listings (place identities, place-pin coordinates and displayed schedules):

- [Belvedere Park](https://www.google.com/maps/place/Belvedere+Park/data=!4m2!3m1!1s0x12fd34819aa171bf:0x77a533be27aa925d)
- [Habib Thameur Park](https://www.google.com/maps/place/Habib+Thameur+Park/data=!4m2!3m1!1s0x12fd355779bc5e31:0x7125ad28152acea7)
- [National Library of Tunisia](https://www.google.com/maps/place/National+Library+of+Tunisia/data=!4m2!3m1!1s0x12fd34753bf91241:0x593ca907ae5bbf22)
- [Tunisia Mall](https://www.google.com/maps/place/Tunisia+Mall/data=!4m2!3m1!1s0x12fd4ab44df809c9:0x97b81064f47410e2)

Other evidence:

- [Ministry of Environment: Belvédère park](https://environnement.gov.tn/tunisie-environnement/lenvironnement-urbain/biodiversite-urbaine): park identity and vegetation.
- [Mapcarta / OpenStreetMap: Jardin Habib Thameur](https://mapcarta.com/36387024): central garden location, independently of the Google place pin.
- [National Library contact](https://www.bibliotheque.nat.tn/BNT/contact.aspx?_lg=fr-FR): street address.
- [National Library visitor rules and hours](https://www.bibliotheque.nat.tn/BNT/accueil-orientation.aspx): reception/room schedules and entry restrictions. These take precedence over the different Maps schedule.
- [Tunisia Mall](https://tunisia-mall.com/): address, air conditioning, food court, accessibility and internally conflicting opening hours. This does not verify free potable water or designation as a cooling shelter.

## Verification

```powershell
php vendor/bin/pest tests/Feature/TunisCoolingPointSeederTest.php tests/Feature/HeatDistressAssistantTest.php tests/Feature/CoolingPointCrudTest.php
```

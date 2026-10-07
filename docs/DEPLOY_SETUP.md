# Add the StoryIngest module to the repo and set up deploy branches.

First, commit the module to a feature branch and push to the fork:

```bash
cd /home/csd/repos/colorado-sun-wp
git checkout -b feature/story-ingest-module
git add inc/Modules/StoryIngest/
git commit -m "feat: add StoryIngest module for save_post webhook to NewsroomOS"
git push fork feature/story-ingest-module
```

Then create the `alpha` (staging deploy) and `release` (production deploy) branches on the org repo. These must exist for the GitHub Actions workflows to trigger on push:

```bash
# Create alpha branch from trunk (on org)
git checkout trunk
git push origin trunk:alpha
git push origin trunk:release
```

Two repo-level **secrets** and two **variables** are needed for the deploy to work:

**Secrets (Settings → Secrets and Variables → Actions → Secrets):**
- `STAGING_SFTP_USER_COLORADOSUN_COM` — SFTP username for staging
- `STAGING_SFTP_PASSWORD_COLORADOSUN_COM` — SFTP password for staging

(If not set, the deploy workflow checks `if: ${{ vars.STAGING_SITES != '' }}` and skips the deploy step.)

**Variables (Settings → Secrets and Variables → Actions → Variables):**
- `STAGING_SITES` — JSON array: `["COLORADOSUN_COM"]`
- `PROD_SITES` — can be set to `[]` for now (no production deploy until ready)

The SFTP credentials come from Newspack — ask them to create the SFTP user and provide the username/password, or configure via their tooling (they mention they can do it automatically).

**The module also needs the two constants set on the staging WP site** (in `wp-config.php` or similar):

```php
define( 'COLORADO_SUN_STORY_WEBHOOK_URL', 'https://staging-newsroom.cosun.xyz/api/stories/upsert' );
define( 'COLORADO_SUN_STORY_WEBHOOK_KEY', '6327c6f448b75e218bf0a0e3f61f8e0ca28d419ca3dcc818a592d4735538f68f' );
```

After alpha branch is pushed and secrets configured:
1. Merge PR into `trunk`
2. Merge `trunk` into `alpha` → GitHub Action deploys to staging
3. On the staging WP admin, go to **Settings → Custom Modules** and enable **Story Ingest**
4. Create/update a post on staging WP → watch the webhook fire to staging-newsroom.cosun.xyz
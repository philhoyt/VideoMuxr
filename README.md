# VideoMuxr

Uploads video straight from the browser to Mux for transcoding and plays it back with the Mux player.

VideoMuxr sends video uploads to [Mux](https://mux.com/) instead of the WordPress uploads folder. The browser PUTs the file to a Mux direct-upload URL, Mux transcodes it (HDR is converted to SDR), and the post renders a `<mux-player>` element. Nothing is transcoded on the server, so FFmpeg is not needed. It ships a block for the block editor and a small REST and PHP API that other plugins can call; [QuickPostr](https://github.com/philhoyt/QuickPostr) uses it for videos posted from its composer.

## Requirements

- WordPress 6.7 or later
- PHP 8.1 or later
- A Mux account and an Access Token with the Mux Video: Full Access permission
- Visitors' browsers load the player from the jsDelivr CDN and stream from Mux

## Installation

1. Download `videomuxr.zip` from the latest [release](https://github.com/philhoyt/VideoMuxr/releases) and upload it under Plugins > Add New > Upload Plugin, or copy the folder to `wp-content/plugins/videomuxr`.
2. Activate the plugin.
3. Go to Settings > VideoMuxr and enter the Mux Token ID and Token Secret.

The zip includes `build/`, and the bundled Plugin Update Checker reads later releases from GitHub, so updates appear on the Plugins screen.

If you clone the repository, build first:

```bash
npm install
npm run build
```

## Usage

### The block

Add a VideoMuxr Video block (Media category) and choose a file. The block requests an upload URL, uploads with progress, then polls until Mux reports the asset ready and stores the playback ID, asset ID and aspect ratio as block attributes. On the front end it renders `<mux-player>` sized to that aspect ratio, so the layout does not shift when the player loads. Replace video in the block toolbar deletes the old Mux asset and starts over.

Until credentials are saved, uploads fail and the REST endpoints return 503.

### PHP helpers

`includes/functions.php` is loaded on every request:

```php
videomuxr_is_configured(): bool
videomuxr_get_playback_id( int $post_id ): ?string
videomuxr_get_player_html( string $playback_id, array $attrs = [] ): string
```

`videomuxr_get_playback_id()` reads the `_videomuxr_playback_id` post meta. `videomuxr_get_player_html()` returns a `<mux-player>` tag with the given attributes; the caller is responsible for the player script (see below).

### Post meta

Two private, single-value meta keys are registered on `post`: `_videomuxr_playback_id` and `_videomuxr_asset_id`. They are not exposed over REST. VideoMuxr itself does not write them; companion plugins do (QuickPostr sets them when it creates a video post). They drive two things:

- On singular post pages, the Mux player script (`https://cdn.jsdelivr.net/npm/@mux/mux-player@3`, as an ES module) is enqueued when the post has a playback ID. The block enqueues the same script itself when it renders.
- When a post with `_videomuxr_asset_id` is permanently deleted, the Mux asset is deleted too. Revisions, autosaves and other post types are ignored.

## REST API

All routes are under `videomuxr/v1` and require `edit_posts`. Each returns a 503 error while Mux credentials are missing.

| Method | Route | Description |
|---|---|---|
| POST | `/direct-upload` | Creates a Mux direct upload with a public playback policy and the site's `home_url()` as the CORS origin. Returns `upload_id` and `upload_url`; PUT the file to `upload_url`. |
| GET | `/upload-status?upload_id=` | Returns `status` (`waiting`, `asset_created`, `ready` or `errored`). `playback_id` and `aspect_ratio` (for example `16:9`) are included only once the status is `ready`. |
| DELETE | `/asset` | Deletes a Mux asset. Pass `post_id` to delete the asset in that post's meta (requires `edit_post` on it; the meta is cleared afterwards) or `asset_id` to delete by ID. |

Requests use the normal WordPress cookie and nonce authentication.

## Development

```bash
npm install
composer install

npm start            # webpack watch
npm run build        # production build into build/
npm run lint:js
npm run lint:css

composer lint        # phpcs (WPCS)
composer lint:fix    # phpcbf
composer analyse     # PHPStan, using phpstan-bootstrap.php for the constants
composer test        # PHPUnit unit suite (Brain Monkey, no WordPress)
```

The integration suite runs inside wp-env (Docker):

```bash
npm run env:start    # WordPress with the plugin active, PHP 8.1
npm run test:php     # unit and integration suites in the tests container
npm run env:stop
```

`composer install` and `composer update` copy Plugin Update Checker into `lib/`, which ships in the zip; `vendor/` does not.

CI runs `composer audit`, phpcs, PHPStan and the unit suite on PHP 8.1 through 8.4, lints and builds the JS, and runs `npm run test:php` in wp-env.

## Releases

Bump the version in `videomuxr.php` (header and `VIDEOMUXR_VERSION`), `phpstan-bootstrap.php`, `package.json`, `src/blocks/videomuxr-video/block.json` and the readme.txt `Stable tag`, add the changelog entry, then push a `v`-prefixed tag:

```bash
git tag v0.1.1 && git push origin v0.1.1
```

The release workflow installs dependencies, runs `npm run build` and `npm run plugin-zip`, takes the matching `= X.Y.Z =` section from readme.txt as the release notes, and attaches `videomuxr.zip` and `readme.txt` to the GitHub release.

## Limitations

- Credentials live in the `videomuxr_settings` option. There is no wp-config.php override yet.
- Every asset gets a public playback policy. Signed playback is not supported.
- The block keeps its IDs in block attributes, not post meta, so deleting a post that only used the block does not delete the asset from Mux. Use Replace video in the block, or the Mux dashboard.
- The front-end player script is only auto-enqueued on singular `post` pages with the meta set; callers of `videomuxr_get_player_html()` elsewhere must enqueue it themselves.
- Uploads from the block go straight to Mux; nothing is stored in the Media Library.

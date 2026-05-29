=== VideoMuxr ===
Contributors:      philhoyt
Tags:              video, mux, upload, player
Requires at least: 6.7
Tested up to:      7.0
Requires PHP:      8.1
Stable tag:        0.1.0
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Routes WordPress video uploads through Mux for transcoding and playback.

== Description ==

VideoMuxr is a standalone WordPress companion plugin that routes video uploads through Mux. It exposes REST endpoints for direct upload and status polling, stores Mux asset and playback IDs as post meta, and provides a public helper API that other plugins can call.

Mux automatically transcodes uploaded video — including HDR to SDR conversion — so videos play correctly in all browsers without requiring FFmpeg on the server.

= Features =

* Direct upload to Mux via REST API — no server-side file handling
* Status polling endpoint — returns playback ID once Mux finishes transcoding
* Automatic Mux asset deletion when a post is permanently deleted
* Public helper functions: `videomuxr_is_configured()`, `videomuxr_get_playback_id()`, `videomuxr_get_player_html()`
* Front-end `<mux-player>` web component loaded via CDN on posts that have a playback ID

= Requirements =

* WordPress 6.7 or higher
* PHP 8.1 or higher
* A Mux account with API credentials (Token ID and Token Secret)

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/videomuxr` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Go to **Settings → VideoMuxr** and enter your Mux Token ID and Token Secret.

== Frequently Asked Questions ==

= Where do I get Mux API credentials? =

Sign up at [mux.com](https://mux.com/) and create an Access Token under Settings → Access Tokens. The token needs `Mux Video: Full Access`.

= Can I define credentials in wp-config.php? =

Not yet — credentials are stored in the database via the Settings page. A wp-config.php override may be added in a future release.

== Changelog ==

= 0.1.0 =
* Add: VideoMuxr Video block — upload video directly in the block editor, transcoded by Mux and played via `<mux-player>` on the front end.
* Add: REST API (`videomuxr/v1`) for direct upload, status polling, and asset deletion.
* Add: Settings → VideoMuxr page for Mux Token ID and Token Secret.
* Add: Public helper functions `videomuxr_is_configured()`, `videomuxr_get_playback_id()`, and `videomuxr_get_player_html()`.
* Add: Automatic Mux asset deletion when a post is permanently deleted.
* Change: Front-end player uses each video's real aspect ratio reported by Mux, preventing layout shift on load.

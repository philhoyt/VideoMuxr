=== VideoMuxr ===
Contributors:      philhoyt
Tags:              video, mux, upload, player
Requires at least: 6.7
Tested up to:      7.1
Requires PHP:      8.1
Stable tag:        0.1.1
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Uploads video straight from the browser to Mux for transcoding and plays it back with the Mux player

== Description ==

VideoMuxr sends video uploads to Mux instead of the WordPress uploads folder. The browser uploads the file directly to Mux, Mux transcodes it (HDR is converted to SDR), and the post plays it through the `<mux-player>` web component. Nothing is transcoded on your server, so FFmpeg is not needed.

The plugin adds:

* A VideoMuxr Video block. Pick a file in the block editor, watch it upload and process, and the block renders the player with the video's own aspect ratio, so the page does not shift when the player loads.
* REST endpoints under `videomuxr/v1` for requesting an upload URL, polling until the video is ready, and deleting an asset.
* Post meta keys `_videomuxr_playback_id` and `_videomuxr_asset_id` for companion plugins, and helper functions `videomuxr_is_configured()`, `videomuxr_get_playback_id()` and `videomuxr_get_player_html()`.
* Deletion of the Mux asset when a post carrying that meta is permanently deleted.

QuickPostr (https://github.com/philhoyt/QuickPostr) uses VideoMuxr as a companion: when both are active, videos posted from its composer go to Mux.

The player script is loaded from the jsDelivr CDN on posts that use it, so visitors' browsers connect to jsdelivr.net and Mux's streaming servers.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/videomuxr`, or install the zip through the Plugins screen.
2. Activate the plugin.
3. Go to Settings > VideoMuxr and enter your Mux Token ID and Token Secret.

Until credentials are saved, the REST endpoints return a 503 error and uploads from the block fail.

== Frequently Asked Questions ==

= Where do I get Mux API credentials? =

Sign up at https://mux.com/ and create an Access Token under Settings > Access Tokens with the Mux Video: Full Access permission.

= Can I define credentials in wp-config.php? =

Not yet. Credentials are stored in the `videomuxr_settings` option through the Settings page.

= Who can upload? =

The REST endpoints require the edit_posts capability. Deleting an asset by post ID also requires permission to edit that post.

= Are uploaded videos public? =

Yes. Assets are created with a public playback policy, so anyone with the playback ID can stream the video.

= Does deleting a post delete the video from Mux? =

Only when the post carries the `_videomuxr_asset_id` meta, which companion plugins such as QuickPostr set. The VideoMuxr Video block stores its IDs as block attributes, not meta, so removing a block or deleting a post that only used the block leaves the asset in Mux. Replace video in the block toolbar deletes the old asset before uploading a new one; otherwise delete it from the Mux dashboard.

== Changelog ==

= 0.1.1 =
* Fixed update checks failing with a missing class: the update checker's Markdown and readme parsers now ship with the plugin.
* Tested up to WordPress 7.1.

= 0.1.0 =
* Added the VideoMuxr Video block: upload a video in the block editor, transcoded by Mux and played with `<mux-player>` on the front end.
* Added the `videomuxr/v1` REST API for direct upload, status polling and asset deletion.
* Added the Settings > VideoMuxr page for the Mux Token ID and Token Secret.
* Added the helper functions `videomuxr_is_configured()`, `videomuxr_get_playback_id()` and `videomuxr_get_player_html()`.
* Added deletion of the Mux asset when a post is permanently deleted.
* Changed the front-end player to use the aspect ratio Mux reports for each video, so the layout does not shift on load.

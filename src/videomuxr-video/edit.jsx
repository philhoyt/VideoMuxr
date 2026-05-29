/**
 * VideoMuxr Video block — editor component.
 *
 * Four-state upload machine:
 *   idle       — no video yet, shows Upload button
 *   uploading  — XHR PUT to Mux, shows progress
 *   processing — polling /upload-status, shows spinner
 *   ready      — playbackId stored, shows placeholder + toolbar actions
 */

import { useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { BlockControls, useBlockProps } from '@wordpress/block-editor';
import {
	Button,
	Spinner,
	ToolbarButton,
	ToolbarGroup,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

const STATE = {
	IDLE: 'idle',
	UPLOADING: 'uploading',
	PROCESSING: 'processing',
	READY: 'ready',
};

/**
 * @param {Object}   props
 * @param {Object}   props.attributes
 * @param {Function} props.setAttributes
 */
export default function Edit( { attributes, setAttributes } ) {
	const { playbackId, assetId } = attributes;
	const blockProps = useBlockProps( {
		className: 'videomuxr-video-edit',
	} );

	const [ uploadState, setUploadState ] = useState(
		playbackId ? STATE.READY : STATE.IDLE
	);
	const [ progress, setProgress ] = useState( 0 );
	const [ uploadId, setUploadId ] = useState( '' );
	const [ error, setError ] = useState( '' );

	const fileInputRef = useRef( null );
	const pollRef = useRef( null );

	// Clear polling interval on unmount.
	useEffect( () => {
		return () => {
			if ( pollRef.current ) {
				clearInterval( pollRef.current );
			}
		};
	}, [] );

	function openFilePicker() {
		fileInputRef.current?.click();
	}

	async function onFileSelected( event ) {
		const file = event.target.files?.[ 0 ];
		if ( ! file ) {
			return;
		}
		// Reset file input so the same file can be reselected after Replace.
		event.target.value = '';

		setError( '' );
		setProgress( 0 );
		setUploadState( STATE.UPLOADING );

		try {
			const { upload_id: newUploadId, upload_url: uploadUrl } =
				await apiFetch( {
					path: '/videomuxr/v1/direct-upload',
					method: 'POST',
				} );

			await uploadToMux( file, uploadUrl );

			setUploadId( newUploadId );
			setUploadState( STATE.PROCESSING );
			startPolling( newUploadId );
		} catch ( err ) {
			setError(
				err?.message || __( 'Upload failed. Please try again.', 'videomuxr' )
			);
			setUploadState( STATE.IDLE );
		}
	}

	/**
	 * Upload a file directly to Mux via PUT.
	 *
	 * @param {File}   file      The video file to upload.
	 * @param {string} uploadUrl The Mux direct upload URL.
	 * @return {Promise<void>}
	 */
	function uploadToMux( file, uploadUrl ) {
		return new Promise( ( resolve, reject ) => {
			const xhr = new XMLHttpRequest();
			xhr.open( 'PUT', uploadUrl );
			xhr.upload.onprogress = ( evt ) => {
				if ( evt.lengthComputable ) {
					setProgress( Math.round( ( evt.loaded / evt.total ) * 100 ) );
				}
			};
			xhr.onload = () => {
				if ( xhr.status >= 200 && xhr.status < 300 ) {
					resolve();
				} else {
					reject( new Error( `HTTP ${ xhr.status }` ) );
				}
			};
			xhr.onerror = () =>
				reject(
					new Error( __( 'Network error during upload.', 'videomuxr' ) )
				);
			xhr.send( file );
		} );
	}

	/**
	 * Begin polling the upload-status endpoint.
	 *
	 * @param {string} id The upload_id to poll.
	 */
	function startPolling( id ) {
		pollRef.current = setInterval( async () => {
			try {
				const result = await apiFetch( {
					path: `/videomuxr/v1/upload-status?upload_id=${ encodeURIComponent( id ) }`,
				} );

				if ( result.status === 'errored' ) {
					clearInterval( pollRef.current );
					setError( __( 'Mux processing failed.', 'videomuxr' ) );
					setUploadState( STATE.IDLE );
				} else if ( result.status === 'ready' && result.playback_id ) {
					clearInterval( pollRef.current );
					setAttributes( {
						playbackId: result.playback_id,
						assetId: result.asset_id || '',
					} );
					setUploadState( STATE.READY );
				}
			} catch {
				// Keep polling on transient network errors.
			}
		}, 5000 );
	}

	async function handleReplace() {
		if ( pollRef.current ) {
			clearInterval( pollRef.current );
		}
		if ( assetId ) {
			try {
				await apiFetch( {
					path: `/videomuxr/v1/asset?asset_id=${ encodeURIComponent( assetId ) }`,
					method: 'DELETE',
				} );
			} catch {
				// Proceed to idle even if the Mux delete fails.
			}
		}
		setAttributes( { playbackId: '', assetId: '' } );
		setUploadId( '' );
		setProgress( 0 );
		setError( '' );
		setUploadState( STATE.IDLE );
	}

	return (
		<>
			{ uploadState === STATE.READY && (
				<BlockControls>
					<ToolbarGroup>
						<ToolbarButton
							icon="controls-repeat"
							label={ __( 'Replace video', 'videomuxr' ) }
							onClick={ handleReplace }
						/>
					</ToolbarGroup>
				</BlockControls>
			) }

			<figure { ...blockProps }>
				{ /* eslint-disable-next-line jsx-a11y/click-events-have-key-events,jsx-a11y/no-static-element-interactions */ }
				<input
					ref={ fileInputRef }
					type="file"
					accept="video/*"
					className="videomuxr-video__file-input"
					onChange={ onFileSelected }
				/>

				{ uploadState === STATE.IDLE && (
					<div className="videomuxr-video__placeholder">
						{ error && (
							<p className="videomuxr-video__error">{ error }</p>
						) }
						<span className="videomuxr-video__icon dashicons dashicons-video-alt3" />
						<p className="videomuxr-video__label">
							{ __( 'VideoMuxr Video', 'videomuxr' ) }
						</p>
						<Button variant="primary" onClick={ openFilePicker }>
							{ __( 'Upload Video', 'videomuxr' ) }
						</Button>
					</div>
				) }

				{ uploadState === STATE.UPLOADING && (
					<div className="videomuxr-video__placeholder">
						<p className="videomuxr-video__label">
							{ __( 'Uploading…', 'videomuxr' ) } { progress }%
						</p>
						<div className="videomuxr-video__progress">
							<div
								className="videomuxr-video__progress-fill"
								style={ { width: `${ progress }%` } }
							/>
						</div>
					</div>
				) }

				{ uploadState === STATE.PROCESSING && (
					<div className="videomuxr-video__placeholder">
						<Spinner />
						<p className="videomuxr-video__label">
							{ __( 'Processing with Mux…', 'videomuxr' ) }
						</p>
					</div>
				) }

				{ uploadState === STATE.READY && (
					<div className="videomuxr-video__preview">
						<span className="videomuxr-video__preview-icon dashicons dashicons-video-alt3" />
						<p className="videomuxr-video__preview-title">
							{ __( 'Mux Video', 'videomuxr' ) }
						</p>
						<p className="videomuxr-video__preview-id">
							{ playbackId }
						</p>
					</div>
				) }
			</figure>
		</>
	);
}

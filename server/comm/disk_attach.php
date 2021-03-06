<?php

	$sb_status = sb_status_fetch();

	$vmuuid = $sb_status['setting9'];

	sleep( 2 );//wait in case detatch of same disk not ready
	$status    = 0;
	$reason    = 'None';
	$newvmuuid = '';

	if ( $sb_status['status'] == 'restore' && $sb_status['stage'] == 'disk_attach' ) {

		if ( $sb_status['setting3'] == '-XEN-' ) {
			$filepath = $settings['mount_migrate'] . '/';
		} else {
			$filepath = $settings['mount_backups'] . '/' . $sb_status['setting1'] . '/' . $sb_status['setting2'] . '/' . $sb_status['setting3'] . '/';
		}
		$processdisks = sb_disk_array_fetch( $filepath );
		$bootabledisk = 0;

		foreach ( $processdisks as $processdisk ) {
			if ( (string) $processdisk['bootable'] == (string) 'true' ) {
				$disknumber = str_replace( 'Disk', '', $processdisk['disknumber'] );
				sb_log( 'BOOTABLE - ' . $disknumber );
				$bootabledisk = $disknumber;
			}
		}

		$attachments     = file_get_contents( $projectpath . 'cache/diskattachitems.dat' );
		$attachments     = explode( "\n", $attachments );
		$disknumbercheck = 1;
		foreach ( $attachments as $attachment ) {
			if ( ! empty( $attachment ) ) {

				$attachmentdata = explode( " ", $attachment );

				if ( $sb_status['setting3'] == '-XEN-' ) {
					$bootable = ( $bootabledisk == $disknumbercheck) ? 'true' : 'false';
				} else {
					$bootable = ( $bootabledisk == $disknumbercheck) ? 'true' : 'false';
				}

                exec( 'cat ' . $filepath . 'Disk' . $disknumbercheck . '.dat', $datfiledata );
                $drive_interface = $datfiledata[4];
                sb_log( '-- DISK ATTACH -- ' . $disknumbercheck . ' AS ' . $drive_interface  . ' bootable: ' . $bootable );

				sb_log( '-- DISK ATTACH -- ' . $disknumbercheck . ' AS ' . $drive_interface  . ' bootable: ' . $bootable );

				$xml = '<disk_attachment>
				        <disk id="' . $attachmentdata[1] . '">
				        </disk>
				        <active>true</active>
				        <bootable>' . $bootable . '</bootable>
				        <interface>' . $drive_interface . '</interface>
				        </disk_attachment>';

				sb_log( $xml );

				$attachdisk = ovirt_rest_api_call( 'POST', 'vms/' . $vmuuid . '/diskattachments/', $xml );
				$disknumbercheck ++;
                sleep( 1 );
			}
		}

		sb_cache_set( $settings['uuid_backup_engine'], '', '', '', 'delete' );
		sb_status_set( 'ready', '', 0 );
		sb_log( 'VM Restore Completed' );

		$status = 1;
		$reason = 'Disk Attached';

	}

	if ( empty( $status ) ) {
		sb_cache_set( $vmuuid, $snapshotname, 'Restore - Attach Disk to New VM Failure - ' . $reason, $vmuuid, 'write' );
	}

	$jsonarray = array(
		"status" => $status,
		"reason" => $reason,
	);


	echo json_encode( $jsonarray );
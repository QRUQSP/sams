<?php
//
// Description
// -----------
// This method will add a new message for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to add the Message to.
//
// Returns
// -------
//
function qruqsp_sams_messageAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'),
        'from_callsign'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'From'),
        'to_callsign'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'To'),
        'content'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Message Content'),
        'hops'=>array('required'=>'no', 'blank'=>'no', 'default'=>'2', 'name'=>'Hops'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'sams', 'private', 'checkAccess');
    $rc = qruqsp_sams_checkAccess($ciniki, $args['tnid'], 'qruqsp.sams.messageAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get a UUID for use in permalink
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
    $rc = ciniki_core_dbUUID($ciniki, 'qruqsp.sams');
    if( $rc['stat'] != 'ok' ) {
       return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.sams.9', 'msg'=>'Unable to get a new UUID', 'err'=>$rc['err']));
    }
    $args['uuid'] = $rc['uuid'];
    $args['msg_id'] = $rc['uuid'];

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'qruqsp.sams');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Add the message to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'qruqsp.sams.message', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'qruqsp.sams');
        return $rc;
    }
    $message_id = $rc['id'];

    //
    // Send the message via aprs
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'sams', 'private', 'messageSend');
    $rc = qruqsp_sams_messageSend($ciniki, $args['tnid'], $message_id);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.sams.10', 'msg'=>'Error sending message', 'err'=>$rc['err']));
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'qruqsp.sams');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'qruqsp', 'sams');

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'qruqsp.sams.message', 'object_id'=>$message_id));

    return array('stat'=>'ok', 'id'=>$message_id);
}
?>

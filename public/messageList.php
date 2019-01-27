<?php
//
// Description
// -----------
// This method will return the list of Messages for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Message for.
//
// Returns
// -------
//
function qruqsp_sams_messageList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'sams', 'private', 'checkAccess');
    $rc = qruqsp_sams_checkAccess($ciniki, $args['tnid'], 'qruqsp.sams.messageList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of messages
    //
    $strsql = "SELECT qruqsp_sams_messages.id, "
        . "qruqsp_sams_messages.msg_id, "
        . "qruqsp_sams_messages.status, "
        . "qruqsp_sams_messages.from_callsign, "
        . "qruqsp_sams_messages.to_callsign, "
        . "qruqsp_sams_messages.path, "
        . "qruqsp_sams_messages.content "
        . "FROM qruqsp_sams_messages "
        . "WHERE qruqsp_sams_messages.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY date_added DESC "
        . "LIMIT 50 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.sams', array(
        array('container'=>'messages', 'fname'=>'id', 
            'fields'=>array('id', 'msg_id', 'status', 'from_callsign', 'to_callsign', 'path', 'content')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['messages']) ) {
        $messages = $rc['messages'];
        $message_ids = array();
        foreach($messages as $iid => $message) {
            $message_ids[] = $message['id'];
        }
    } else {
        $messages = array();
        $message_ids = array();
    }

    return array('stat'=>'ok', 'messages'=>$messages, 'nplist'=>$message_ids);
}
?>

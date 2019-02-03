<?php
//
// Description
// -----------
// Send a message from the queue over aprs.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// message_id:      The ID of the message to send.
// 
// Returns
// ---------
// 
function qruqsp_sams_messageSend(&$ciniki, $tnid, $message_id) {

    //
    // Load the message
    //
    $strsql = "SELECT qruqsp_sams_messages.id, "
        . "qruqsp_sams_messages.msg_id, "
        . "qruqsp_sams_messages.status, "
        . "qruqsp_sams_messages.from_callsign, "
        . "qruqsp_sams_messages.to_callsign, "
        . "qruqsp_sams_messages.path, "
        . "qruqsp_sams_messages.content "
        . "FROM qruqsp_sams_messages "
        . "WHERE qruqsp_sams_messages.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND qruqsp_sams_messages.id = '" . ciniki_core_dbQuote($ciniki, $message_id) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'qruqsp.sams', 'message');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.sams.11', 'msg'=>'Unable to load message', 'err'=>$rc['err']));
    }
    if( !isset($rc['message']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.sams.12', 'msg'=>'Unable to find requested message'));
    }
    $message = $rc['message'];

    //
    // Create the packet
    //
    $packet = array(
        'addrs' => array(
            strtoupper($message['to_callsign']),
            strtoupper($message['from_callsign']),
            'WIDE2-1',
            ),
        'control' => 0x03, 
        'protocol' => 0xf0,
        'data' => sprintf(":%-9s:%s", $message['to_callsign'], $message['content']),
        );

    //
    // FIXME: Add zulu timestamp to end of message for each ACK tracking ' [HHMMSSz]' eg: ' [224854z]'
    //

    //
    // Send the message
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'tnc', 'hooks', 'packetSend');
    $rc = qruqsp_tnc_hooks_packetSend($ciniki, $tnid, array('packet' => $packet));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.sams.11', 'msg'=>'Error sending message', 'err'=>$rc['err']));
    }

    //
    // Change the status
    //
    if( $message['status'] != 40 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'qruqsp.sams.message', $message['id'], array('status'=>40), 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.sams.13', 'msg'=>'Unable to update status', 'err'=>$rc['err']));
        }
    }

    return array('stat'=>'ok');
}
?>

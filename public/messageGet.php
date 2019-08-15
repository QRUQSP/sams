<?php
//
// Description
// ===========
// This method will return all the information about an message.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the message is attached to.
// message_id:          The ID of the message to get the details for.
//
// Returns
// -------
//
function qruqsp_sams_messageGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'message_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Message'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'sams', 'private', 'checkAccess');
    $rc = qruqsp_sams_checkAccess($ciniki, $args['tnid'], 'qruqsp.sams.messageGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Return default for new Message
    //
    if( $args['message_id'] == 0 ) {
        $message = array('id'=>0,
            'msg_id'=>'',
            'status'=>'10',
            'from_callsign'=>$ciniki['session']['user']['username'],
            'to_callsign'=>'',
            'path'=>'',
            'content'=>'',
            'hops'=>'',
        );
    }

    //
    // Get the details for an existing Message
    //
    else {
        $strsql = "SELECT qruqsp_sams_messages.id, "
            . "qruqsp_sams_messages.msg_id, "
            . "qruqsp_sams_messages.status, "
            . "qruqsp_sams_messages.from_callsign, "
            . "qruqsp_sams_messages.to_callsign, "
            . "qruqsp_sams_messages.path, "
            . "qruqsp_sams_messages.content, "
            . "qruqsp_sams_messages.hops "
            . "FROM qruqsp_sams_messages "
            . "WHERE qruqsp_sams_messages.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND qruqsp_sams_messages.id = '" . ciniki_core_dbQuote($ciniki, $args['message_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.sams', array(
            array('container'=>'messages', 'fname'=>'id', 
                'fields'=>array('msg_id', 'status', 'from_callsign', 'to_callsign', 'path', 'content', 'hops'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.sams.7', 'msg'=>'Message not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['messages'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.sams.8', 'msg'=>'Unable to find Message'));
        }
        $message = $rc['messages'][0];
    }

    return array('stat'=>'ok', 'message'=>$message);
}
?>

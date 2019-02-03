<?php
//
// Description
// -----------
// This function accepts a packet that was recieved via TNC and checks if it is
// an aprs packet.
//
// Arguments
// ---------
// q:
// tnid:      
// args: The arguments for the hook
//
function qruqsp_sams_hooks_packetReceived(&$ciniki, $tnid, $args) {
    //
    // If no packet in args, then perhaps a packet we don't understand
    //
    if( !isset($args['packet']['data']) ) {
        error_log('no data');
        return array('stat'=>'ok');
    }

    //
    // Check the control and protocol are correct
    //
    if( !isset($args['packet']['control']) || $args['packet']['control'] != 0x03 
        || !isset($args['packet']['protocol']) || $args['packet']['protocol'] != 0xf0 
        ) {
        error_log('wrong control/protocol');
        return array('stat'=>'ok');
    }

    //
    // Check for a message packet
    //
    if( isset($args['packet']['data'][0]) && $args['packet']['data'][0] == ':'
        && preg_match("/^:([a-zA-Z0-9\- ]{9}):(.*)$/", $args['packet']['data'], $matches) 
        ) {
        error_log('found message');
        $to_callsign = $matches[1];
        $content = $matches[2];

        if( isset($args['packet']['addrs'][1]) ) {
            $from_callsign = $args['packet']['addrs'][1]['callsign'];
        } else {
            $from_callsign = '??';
        }
        error_log('Received: (' . $to_callsign . ') ' . $content);
        error_log(print_r($args['packet']['addrs'], true));

        //
        // Get a UUID for use in permalink
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
        $rc = ciniki_core_dbUUID($ciniki, 'qruqsp.sams');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.sams.15', 'msg'=>'Unable to get a new UUID', 'err'=>$rc['err']));
        }
        $uuid = $rc['uuid'];
        $msg_id = $uuid;

        //
        // FIXME: Check for a message ID
        //


        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'qruqsp.sams.message', array(
            'msg_id' => $msg_id,
            'status' => 70,
            'from_callsign' => $from_callsign,
            'to_callsign' => $to_callsign,
            'path' => '',
            'content' => $content,
            ), 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.sams.14', 'msg'=>'Unable to store aprs message', 'err'=>$rc['err']));
        }
    }

    else {
        error_log($args['packet']['data']);
    }

    //
    // If the packet was parsed, or no aprs data was found, success is returned
    //
    return array('stat'=>'ok');
}
?>

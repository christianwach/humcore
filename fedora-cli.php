<?php
/**
 * HumCORE Fedora API commands.
 *
 * @package HumCORE
 * @subpackage Deposits
 */

class Fedora_Command extends WP_CLI_Command {

    /**
     * Create a collection object.
     * 
     * ## OPTIONS
     * 
     * ## EXAMPLES
     * 
     *     wp fedora create_collection
     *
     * @synopsis
     */
    public function create_collection( $args, $assoc_args ) {

        global $fedora_api;

        if ( empty( $fedora_api->namespace ) ) {
            WP_CLI::error( 'Please add a Namespace on the HumCORE Settings page first.' );
            exit();
        }

        $cStatus = create_collection_object();

        if ( is_wp_error( $cStatus ) ) {
            WP_CLI::error( sprintf( 'Error creating collection object. : %1$s-%2$s', $cStatus->get_error_code(), $cStatus->get_error_message() ) );
        } else {
            // Print a success message
            WP_CLI::success( 'Collection object created.' );
        }
    }

    /**
     * Delete a PID.
     * 
     * ## OPTIONS
     * 
     * <doi>
     * : The PID to be deleted.
     * 
     * ## EXAMPLES
     * 
     *     wp fedora delete --pid="pid"
     *
     * @synopsis --pid=<pid>
     */
    public function delete( $args, $assoc_args ) {

        global $fedora_api;

        $id = $assoc_args['pid'];

        $fStatus = $fedora_api->purge_object( array(
            'pid' => $id,
        ) );

        if ( is_wp_error( $fStatus ) ) {
            WP_CLI::error( sprintf( 'Error deleting pid : %1$s, %2$s-%3$s', $id, $fStatus->get_error_code(), $fStatus->get_error_message() ) );
        } else {
            // Print a success message
            WP_CLI::success( sprintf( 'Deleted pid : %1$s!', $id ) );
        }
    }

    /**
     * Get Object XML for a PID.
     * 
     * ## OPTIONS
     * 
     * <pid>
     * : The PID to be retrieved.
     * 
     * ## EXAMPLES
     * 
     *     wp fedora get_object_xml --pid="pid"
     *
     * @synopsis --pid=<pid>
     */
    public function get_object_xml( $args, $assoc_args ) {

        global $fedora_api;

        $id = $assoc_args['pid'];

        $fStatus = $fedora_api->get_object_xml( array(
            'pid' => $id,
        ) );

        if ( is_wp_error( $fStatus ) ) {
            WP_CLI::error( sprintf( 'Error retrieving object xml pid : %1$s, %2$s-%3$s', $id, $fStatus->get_error_code(), $fStatus->get_error_message() ) );
        } else {
            WP_CLI::line( var_export( $fStatus, true ) );
            // Print a success message
            WP_CLI::success( 'Done!' );
        }
    }

    /**
     * Validate a PID.
     * 
     * ## OPTIONS
     * 
     * <pid>
     * : The PID to be validated.
     * 
     * ## EXAMPLES
     * 
     *     wp fedora validate --pid="pid"
     *
     * @synopsis --pid=<pid>
     */
    public function validate( $args, $assoc_args ) {

        global $fedora_api;

        $id = $assoc_args['pid'];

        $fStatus = $fedora_api->validate( array(
            'pid' => $id,
        ) );

        if ( is_wp_error( $fStatus ) ) {
            WP_CLI::error( sprintf( 'Error validating pid : %1$s, %2$s-%3$s', $id, $fStatus->get_error_code(), $fStatus->get_error_message() ) );
        } else {
            WP_CLI::line( var_export( $fStatus, true ) );
            // Print a success message
            WP_CLI::success( 'Done!' );
        }
    }
}

WP_CLI::add_command( 'fedora', 'Fedora_Command' );

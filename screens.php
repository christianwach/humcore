<?php
/**
 * Screen display functions.
 *
 * @package HumCORE
 * @subpackage Deposits
 */

/**
 * Output the deposits search form.
 *
 * @return string html output
 */
function humcore_deposits_search_form() {

	$default_search_value = bp_get_search_default_text( 'humcore_deposits' );
	$search_value = '';
	if ( ! empty( $_REQUEST['s'] ) ) { $search_value = stripslashes( $_REQUEST['s'] ); }

	$search_form_html = '
<div id="deposits-dir-search" class="dir-search" role="search">
  <form action="" method="post" id="search-deposits-form">
	<label>
	<input type="text" name="s" id="search-deposits-term" value="' . esc_attr( $search_value ) . '" placeholder="'. esc_attr( $default_search_value ) .'" />
	</label>
	<input type="hidden" name="facets" id="search-deposits-facets" />
	<input type="hidden" name="field" id="search-deposits-field" />
	<input type="submit" id="search-deposits-submit" name="search_deposits_submit" value="' . __( 'Search', 'humcore_domain' ) . '" />
  </form>
</div><!-- #deposits-dir-search -->
';

	echo apply_filters( 'humcore_deposits_search_form', $search_form_html ); // XSS OK.
}

/**
 * Render the content for deposits/item/new.
 */
function humcore_new_deposit_form() {

	if ( ! empty( $_POST ) ) {
		$deposit_id = humcore_deposit_file();
		if ( $deposit_id ) {
                	$review_url = sprintf( '/deposits/item/%1$s/review/', $deposit_id );
                	wp_redirect( $review_url );
			exit();
		}
	}

	ob_end_flush(); // We've been capturing output.
	if ( ! humcore_check_externals() ) {
		echo '<h3>New <em>CORE</em> Deposit</h3>';
		echo "<p>We're so sorry, but one of the components of <em>CORE</em> is currently down and it can't accept deposits just now. We're working on it (and we're delighted that you want to share your work) so please come back and try again later.</p>";
		$wp_referer = wp_get_referer();
		printf(
			'<a href="%1$s" class="button white" style="line-height: 1.2em;">Go Back</a>',
			( ! empty( $wp_referer ) && ! strpos( $wp_referer, 'item/new' ) ) ? $wp_referer : '/deposits/'
		);
		return;
	}

	$current_group_id = '';
	preg_match( '~.*?/groups/(.*[^/]?)/deposits/~i', wp_get_referer(), $slug_match );
	if ( ! empty( $slug_match ) ) {
		$current_group_id = BP_Groups_Group::get_id_from_slug( $slug_match[1] );
	}

	$user_id = bp_loggedin_user_id();
	$user_firstname = get_the_author_meta( 'first_name', $user_id );
	$user_lastname = get_the_author_meta( 'last_name', $user_id );
	$prev_val = array();
	if ( ! empty( $_POST ) ) {
		$prev_val = $_POST;
	} else {
		$prev_val['deposit-author-role'] = 'author';
	}
	humcore_display_deposit_form( $current_group_id, $user_id, $user_firstname, $user_lastname, $prev_val );

}

function humcore_display_deposit_form( $current_group_id, $user_id, $user_firstname, $user_lastname, $prev_val ) {

?>

<script type="text/javascript">
	var MyAjax = {
		ajaxurl : '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
		flash_swf_url : '<?php echo esc_url( includes_url( '/js/plupload/Moxie.swf' ) ); ?>',
		silverlight_xap_url : '<?php echo esc_url( includes_url( '/js/plupload/Moxie.xap' ) ); ?>',
		_ajax_nonce : '<?php echo esc_attr( wp_create_nonce( 'file-upload' ) ); ?>',
	};
</script>

<h3>New <em>CORE</em> Deposit</h3>

<form id="deposit-form" name="deposit-form" class="standard-form" method="post" action="" enctype="multipart/form-data">

	<input type="hidden" name="action" id="action" value="deposit_file" />
	<?php wp_nonce_field( 'new_core_deposit', 'new_core_deposit_nonce' ); ?>

        <input type="hidden" name="selected_temp_name" id="selected_temp_name" value="<?php if ( ! empty( $prev_val['selected_temp_name'] ) ) { echo sanitize_text_field( $prev_val['selected_temp_name'] ); } ?>" />
        <input type="hidden" name="selected_file_name" id="selected_file_name" value="<?php if ( ! empty( $prev_val['selected_file_name'] ) ) { echo sanitize_text_field( $prev_val['selected_file_name'] ); } ?>" />
        <input type="hidden" name="selected_file_type" id="selected_file_type" value="<?php if ( ! empty( $prev_val['selected_file_type'] ) ) { echo sanitize_text_field( $prev_val['selected_file_type'] ); } ?>" />
        <input type="hidden" name="selected_file_size" id="selected_file_size" value="<?php if ( ! empty( $prev_val['selected_file_type'] ) ) { echo sanitize_text_field( $prev_val['selected_file_size'] ); } ?>" />
        <input type="hidden" name="deposit-author-uni" id="deposit-author-uni" value="<?php echo bp_get_loggedin_user_username(); ?>" />

        <div id="deposit-file-entry">
                <label for="deposit-file">Select the file you wish to upload and deposit. *</label>
		<div id="container">
			<button id="pickfile">Select File</button> 
	<?php $wp_referer = wp_get_referer();
		printf(
			'<a href="%1$s" class="button white" style="line-height: 1.2em;">Cancel</a>',
			( ! empty( $wp_referer ) && ! strpos( $wp_referer, 'item/new' ) ) ? $wp_referer : '/deposits/'
		);
	?>
		</div>
	</div>
	<div id="deposit-file-entries">
		<div id="filelist">Your browser doesn't have Flash, Silverlight or HTML5 support.</div>
		<div id="progressbar">
			<div id="indicator"></div>
		</div>
		<div id="console"></div>
	</div>

        <p>
        <div id="deposit-published-entry">
                <label for="deposit-published">Has this item been previously published?</label>
                        <input type="radio" name="deposit-published" value="published" <?php if ( ! empty( $prev_val['deposit-published'] ) ) { checked( sanitize_text_field( $prev_val['deposit-published'] ), 'published' ); } ?>>Published &nbsp;
                        <input type="radio" name="deposit-published" value="not-published" <?php if ( ! empty( $prev_val['deposit-published'] ) ) { checked( sanitize_text_field( $prev_val['deposit-published'] ), 'not-published' ); } else { echo 'checked="checked"'; } ?>>Not published &nbsp;
        </div>
        </p>

	<div id="deposit-metadata-entries">
	<p>
	<div id="lookup-doi-entry">
		<label for="lookup-doi">Retrieve information</label>
                <span class="description">Use <a onclick="target='_blank'" href="http://www.sherpa.ac.uk/romeo/">SHERPA/RoMEO</a> to check a journal’s open access policies.</span><br />
		<span class="description">Enter a publisher DOI to automatically retrieve information about your item.</span> <br />
		<input type="text" id="lookup-doi" name="lookup-doi" class="long" value="" placeholder="Enter the publisher DOI for this item." />
		<button onClick="javascript:retrieveDOI(); return false;">Retrieve</button>
		<div id="lookup-doi-message"></div>
	</div>
	</div>
	</p>
	<div id="deposit-title-entry">
		<label for="deposit-title">Title</label>
		<input type="text" id="deposit-title-unchanged" name="deposit-title-unchanged" size="75" class="long" value="<?php if ( ! empty( $prev_val['deposit-title-unchanged'] ) ) {  echo wp_kses( stripslashes( $prev_val['deposit-title-unchanged'] ) , array( 'b' => array(), 'em' => array(), 'strong' => array() ) ); } ?>" />
		<span class="description">*</span>
	</div>
	<p>
	<label for="deposit-genre">Item Type</label>
	<div id="deposit-genre-entry">
		<select name="deposit-genre" id="deposit-genre" class="js-basic-single-required" data-placeholder="Select an item type">
			<option class="level-0" value=""></option>
<?php
	$genre_list = humcore_deposits_genre_list();
	$posted_genre = '';
	if ( ! empty( $prev_val['deposit-genre'] ) ) {
		$posted_genre = sanitize_text_field( $prev_val['deposit-genre'] );
	}
	foreach ( $genre_list as $genre_key => $genre_value ) {
		printf('			<option class="level-0" %1$s value="%2$s">%3$s</option>' . "\n",
			( $genre_key == $posted_genre ) ? 'selected="selected"' : '',
			$genre_key,
			$genre_value
		);
	}
?>
		</select>
		<span class="description">*</span>
	</div>
	</p>
	<div id="deposit-conference-entries">
	<div id="deposit-conference-title-entry">
		<label for="deposit-conference-title-entry-list">Conference Title</label>
		<input type="text" name="deposit-conference-title" size="75" class="text" value="<?php if ( ! empty( $prev_val['deposit-conference-title'] ) ) { echo sanitize_text_field( $prev_val['deposit-conference-title'] ); } ?>" />
	</div>

	<div id="deposit-conference-organization-entry">
		<label for="deposit-conference-organization-entry-list">Conference Host Organization</label>
		<input type="text" name="deposit-conference-organization" size="60" class="text" value="<?php if ( ! empty( $prev_val['deposit-conference-organization'] ) ) { echo sanitize_text_field( $prev_val['deposit-conference-organization'] ); } ?>" />
	</div>

	<div id="deposit-conference-location-entry">
		<label for="deposit-conference-location-entry-list">Conference Location</label>
		<input type="text" name="deposit-conference-location" size="75" class="text" value="<?php if ( ! empty( $prev_val['deposit-conference-location'] ) ) { echo sanitize_text_field( $prev_val['deposit-conference-location'] ); } ?>" />
	</div>

	<div id="deposit-conference-date-entry">
		<label for="deposit-conference-date-entry-list">Conference Date</label>
		<input type="text" name="deposit-conference-date" size="75" class="text" value="<?php if ( ! empty( $prev_val['deposit-conference-date'] ) ) { echo sanitize_text_field( $prev_val['deposit-conference-date'] ); } ?>" />
	</div>
	</div>

	<div id="deposit-meeting-entries">
	<div id="deposit-meeting-title-entry">
		<label for="deposit-meeting-title-entry-list">Meeting Title</label>
		<input type="text" name="deposit-meeting-title" size="75" class="text" value="<?php if ( ! empty( $prev_val['deposit-meeting-title'] ) ) { echo sanitize_text_field( $prev_val['deposit-meeting-title'] ); } ?>" />
	</div>

	<div id="deposit-meeting-organization-entry">
		<label for="deposit-meeting-organization-entry-list">Meeting Host Organization</label>
		<input type="text" name="deposit-meeting-organization" size="60" class="text" value="<?php if ( ! empty( $prev_val['deposit-meeting-organization'] ) ) { echo sanitize_text_field( $prev_val['deposit-meeting-organization'] ); } ?>" />
	</div>

	<div id="deposit-meeting-location-entry">
		<label for="deposit-meeting-location-entry-list">Meeting Location</label>
		<input type="text" name="deposit-meeting-location" size="75" class="text" value="<?php if ( ! empty( $prev_val['deposit-meeting-location'] ) ) { echo sanitize_text_field( $prev_val['deposit-meeting-location'] ); } ?>" />
	</div>

	<div id="deposit-meeting-date-entry">
		<label for="deposit-meeting-date-entry-list">Meeting Date</label>
		<input type="text" name="deposit-meeting-date" size="75" class="text" value="<?php if ( ! empty( $prev_val['deposit-meeting-date'] ) ) { echo sanitize_text_field( $prev_val['deposit-meeting-date'] ); } ?>" />
	</div>
	</div>

	<div id="deposit-institution-entries">
	<div id="deposit-institution-entry">
		<label for="deposit-institution-entry-list">Name of Institution</label>
		<input type="text" name="deposit-institution" size="60" class="text" value="<?php if ( ! empty( $prev_val['deposit-institution'] ) ) { echo sanitize_text_field( $prev_val['deposit-institution'] ); } ?>" />
	</div>
	</div>

	<p>
	<div id="deposit-abstract-entry">
		<label for="deposit-abstract">Description or Abstract</label>
		<textarea class="abstract_area" rows="12" autocomplete="off" cols="80" name="deposit-abstract-unchanged" id="deposit-abstract-unchanged"><?php if ( ! empty( $prev_val['deposit-abstract-unchanged'] ) ) { echo wp_kses( stripslashes( $prev_val['deposit-abstract-unchanged'] ) , array( 'b' => array(), 'em' => array(), 'strong' => array() ) ); } ?></textarea>
		<span class="description">*</span>
	<div class="character-count"></div>
	</div>
	</p>
	<p>
	<div id="deposit-on-behalf-flag-entry">
<?php
        $committee_list = humcore_deposits_user_committee_list( bp_loggedin_user_id() );
        if ( empty( $committee_list ) ) {
?>
        <input type="hidden" name="deposit-on-behalf-flag" id="deposit-on-behalf-flag" value="" />
<?php   } else { ?>
		<label for="deposit-on-behalf-flag-list">Depositor</label>
		<span class="description">Is this deposit being made on behalf of a group?</span>
			<input type="radio" name="deposit-on-behalf-flag" value="yes" <?php if ( ! empty( $prev_val['deposit-on-behalf-flag'] ) ) { checked( sanitize_text_field( $prev_val['deposit-on-behalf-flag'] ), 'yes' ); } ?>>Yes &nbsp;
			<input type="radio" name="deposit-on-behalf-flag" value="no" <?php if ( ! empty( $prev_val['deposit-on-behalf-flag'] ) ) { checked( sanitize_text_field( $prev_val['deposit-on-behalf-flag'] ), 'no' ); } else { echo 'checked="checked"'; } ?>>No &nbsp;
<?php
	} ?>
	</div>
	</p>
	<p>
	<div id="deposit-committee-entry">
<?php
	if ( empty( $committee_list ) ) {
?>
	<input type="hidden" name="deposit-committee" id="deposit-committee" value="" />
<?php	} else { ?>

		<label for="deposit-committee">Deposit Group</label>
		<select name="deposit-committee" id="deposit-committee" class="js-basic-single-optional" data-placeholder="Select group">
			<option class="level-0" selected value=""></option>
<?php
	$posted_committee = '';
	if ( ! empty( $prev_val['deposit-committee'] ) ) { $posted_committee = sanitize_text_field( $prev_val['deposit-committee'] ); }
	foreach ( $committee_list as $committee_key => $committee_value ) {
		printf( '			<option class="level-1" %1$s value="%2$s">%3$s</option>' . "\n",
			( $committee_key == $posted_committee ) ? 'selected="selected"' : '',
			$committee_key,
			$committee_value
		);
	}
?>
		</select>
<?php
	} ?>
	</div>
	</p>
	<p>
	<div id="deposit-other-authors-entry">
		<label for="deposit-other-authors-entry-list">Contributors</label>
		<span class="description">Add any contributors in addition to yourself.</span>
		<div id="deposit-other-authors-entry-list">
		<table id="deposit-other-authors-entry-table"><tbody>
		<tr><td class="noBorderTop" style="width:205px;">
		Given Name
		</td><td class="noBorderTop" style="width:205px;">
		Family Name
		</td><td class="noBorderTop">
		Role
		</td><td class="noBorderTop">
		<input type="button" id="deposit-insert-other-author-button" class="button add_author" value="Add a Contributor">
		</td></tr>
		<tr id="deposit-author-display"><td class="borderTop" style="width:205px;">
		<?php echo esc_html( $user_firstname ); ?>
		<input type="hidden" name="deposit-author-first-name" id="deposit-author-first-name" value="<?php echo esc_html( $user_firstname ); ?>" />
		</td><td class="borderTop" style="width:205px;">
		<?php echo esc_html( $user_lastname ); ?>
		<input type="hidden" name="deposit-author-last-name" id="deposit-author-last-name" value="<?php echo esc_html( $user_lastname ); ?>" />
		</td><td class="borderTop" style="width:230px;">
		<span style="white-space: nowrap;"><input type="radio" name="deposit-author-role" class="styled" value="author" <?php if ( ! empty( $prev_val['deposit-author-role'] ) ) { checked( sanitize_text_field( $prev_val['deposit-author-role'] ), 'author' ); } ?>>Author &nbsp;</span>
		<span style="white-space: nowrap;"><input type="radio" name="deposit-author-role" class="styled" value="editor" <?php if ( ! empty( $prev_val['deposit-author-role'] ) ) { checked( sanitize_text_field( $prev_val['deposit-author-role'] ), 'editor' ); } ?>>Editor &nbsp;</span>
		<?php if ( is_super_admin() ) : ?>
		<span style="white-space: nowrap;"><input type="radio" name="deposit-author-role" class="styled" value="submitter" <?php if ( ! empty( $prev_val['deposit-author-role'] ) ) { checked( sanitize_text_field( $prev_val['deposit-author-role'] ), 'submitter' ); } ?>>Submitter &nbsp;</span>
		<?php endif; ?>
		<span style="white-space: nowrap;"><input type="radio" name="deposit-author-role" class="styled" value="translator" <?php if ( ! empty( $prev_val['deposit-author-role'] ) ) { checked( sanitize_text_field( $prev_val['deposit-author-role'] ), 'translator' ); } ?>>Translator &nbsp;</span>
		</td><td class="borderTop">
		</td></tr>

<?php
	if ( ! empty( $prev_val['deposit-other-authors-first-name'] ) && ! empty( $prev_val['deposit-other-authors-last-name'] ) ) {
		$other_authors = array_map(
			function ( $first_name, $last_name, $role ) {
				return array( 'first_name' => sanitize_text_field( $first_name ),
					'last_name' => sanitize_text_field( $last_name ),
					'role' => sanitize_text_field( $role ) ); },
			$prev_val['deposit-other-authors-first-name'],
			$prev_val['deposit-other-authors-last-name'],
			$prev_val['deposit-other-authors-role']
		);
		foreach ( $other_authors as $author_array ) {
			if ( ! empty( $author_array['first_name'] ) && ! empty( $author_array['last_name'] ) ) {
?>
		<tr><td class="borderTop" style="width:205px;">
		<input type="text" name="deposit-other-authors-first-name[]" class="text" value="<?php echo $author_array['first_name']; ?>" />
		</td><td class="borderTop" style="width:205px;">
		<input type="text" name="deposit-other-authors-last-name[]" class="text deposit-other-authors-last-name" value="<?php echo $author_array['last_name']; ?>" />
		</td><td class="borderTop" style="width:230px; vertical-align: top;">
		<span style="white-space: nowrap;"><input type="radio" name="deposit-other-authors-role[]" class="styled" style="margin-top: 12px;" value="author" <?php if ( ! empty( $author_array['role'] ) ) { checked( sanitize_text_field( $author_array['role'] ), 'author' ); } ?>>Author &nbsp;</span>
		<span style="white-space: nowrap;"><input type="radio" name="deposit-other-authors-role[]" class="styled" style="margin-top: 12px;" value="editor" <?php if ( ! empty( $author_array['role'] ) ) { checked( sanitize_text_field( $author_array['role'] ), 'editor' ); } ?>>Editor &nbsp;</span>
		<span style="white-space: nowrap;"><input type="radio" name="deposit-other-authors-role[]" class="styled" style="margin-top: 12px;" value="translator" <?php if ( ! empty( $author_array['role'] ) ) { checked( sanitize_text_field( $author_array['role'] ), 'translator' ); } ?>>Translator &nbsp;</span>
		</td><td class="borderTop">
		</td></tr>
<?php
			}
		}
	}
?>
		</tbody></table>
		</div>
	</div>
	</p>
	<p>
	<div id="deposit-group-entry">
		<label for="deposit-group">Groups</label>
		<span class="description">Share this item with up to five groups that you are a member of.<br />Selecting a group will notify members of that group about your deposit.</span><br />
		<select name="deposit-group[]" id="deposit-group[]" class="js-basic-multiple" multiple="multiple" data-placeholder="Select groups">
<?php
	$group_list = humcore_deposits_group_list( bp_loggedin_user_id() );
	$posted_group_list = array();
	if ( ! empty( $prev_val['deposit-group'] ) ) { $posted_group_list = array_map( 'sanitize_text_field', $prev_val['deposit-group'] ); }
	foreach ( $group_list as $group_key => $group_value ) {
		printf( '			<option class="level-1" %1$s value="%2$s">%3$s</option>' . "\n",
			( $current_group_id == $group_key || in_array( $group_key, $posted_group_list ) ) ? 'selected="selected"' : '',
			$group_key,
			$group_value
		);
	}
?>
		</select>
	</div>
	</p>
	<p>
	<div id="deposit-subject-entry">
		<label for="deposit-subject">Subjects</label>
		<span class="description">Assign up to five subject fields to your item.<br />Please let us know if you would like to <a href="mailto:commons@mla.org?subject=CORE" target="_blank">suggest additional subject
 fields</a>.</span><br />
		<select name="deposit-subject[]" id="deposit-subject[]" class="js-basic-multiple" multiple="multiple" data-placeholder="Select subjects">
<?php
	$subject_list = humcore_deposits_subject_list();
	$posted_subject_list = array();
	if ( ! empty( $prev_val['deposit-subject'] ) ) {
		$posted_subject_list = array_map( 'sanitize_text_field', $prev_val['deposit-subject'] );
	}
	foreach ( $subject_list as $subject_key => $subject_value ) {
		printf('			<option class="level-1" %1$s value="%2$s">%3$s</option>' . "\n",
			( in_array( $subject_key, $posted_subject_list ) ) ? 'selected="selected"' : '',
			$subject_key,
			$subject_value
		);
	}
?>
		</select>
	</div>
	</p>
	<p>
	<div id="deposit-keyword-entry">
		<label for="deposit-keyword">Tags</label>
		<span class="description">Enter up to five tags to further categorize this item.</span><br />
		<select name="deposit-keyword[]" id="deposit-keyword[]" class="js-basic-multiple-tags" multiple="multiple" data-placeholder="Enter tags">
<?php
	$keyword_list = humcore_deposits_keyword_list();
	$posted_keyword_list = array();
	if ( ! empty( $prev_val['deposit-keyword'] ) ) {
		$posted_keyword_list = array_map( 'sanitize_text_field', $prev_val['deposit-keyword'] );
	}
	foreach ( $keyword_list as $keyword_key => $keyword_value ) {
		printf('			<option class="level-1" %1$s value="%2$s">%3$s</option>' . "\n",
			( in_array( $keyword_key, $posted_keyword_list ) ) ? 'selected="selected"' : '',
			$keyword_key,
			$keyword_value
		);
	}
?>
		</select>
	</div>
	</p>
	<p>
	<label for="deposit-resource-type">File Type</label>
	<div id="deposit-resource-type-entry">
		<select name="deposit-resource-type" id="deposit-resource-type" class="js-basic-single-optional" data-placeholder="Select a file type" data-allowClear="true">
			<option class="level-0" selected="selected" value=""></option>

<?php
	$resource_type_list = humcore_deposits_resource_type_list();
	$posted_resource_type = '';
	if ( ! empty( $prev_val['deposit-resource-type'] ) ) {
		$posted_resource_type = sanitize_text_field( $prev_val['deposit-resource-type'] );
	}
	foreach ( $resource_type_list as $resource_key => $resource_value ) {
		printf('			<option class="level-0" %1$s value="%2$s">%3$s</option>' . "\n",
			( $resource_key == $posted_resource_type ) ? 'selected="selected"' : '',
			$resource_key,
			$resource_value
		);
	}
?>
		</select>
	</div>
	</p>
	<p>
	<label for="deposit-language">Language</label>
	<div id="deposit-language-entry">
		<select name="deposit-language" id="deposit-language" class="js-basic-single-optional" data-placeholder="Select a language" data-allowClear="true">
			<option class="level-0" selected="selected" value=""></option>

<?php
	$language_list = humcore_deposits_language_list();
	$posted_language = '';
	if ( ! empty( $prev_val['deposit-language'] ) ) {
		$posted_language = sanitize_text_field( $prev_val['deposit-language'] );
	}
	foreach ( $language_list as $language_key => $language_value ) {
		printf('			<option class="level-0" %1$s value="%2$s">%3$s</option>' . "\n",
			( $language_key == $posted_language ) ? 'selected="selected"' : '',
			$language_key,
			$language_value
		);
	}
?>
		</select>
	</div>
	</p>
	<p>
	<div id="deposit-notes-entry">
		<label for="deposit-notes">Notes or Background</label>
		<span class="description">Any additional information about your item?</span><br />
		<textarea name="deposit-notes-unchanged" class="the-notes" id="deposit-notes-unchanged"><?php if ( ! empty( $prev_val['deposit-notes-unchanged'] ) ) { echo wp_kses( stripslashes( $prev_val['deposit-notes-unchanged'] ) , array( 'b' => array(), 'em' => array(), 'strong' => array() ) ); } ?></textarea>
	<div class="character-count"></div>
	</div>
	</p>
	<p>
	<div id="deposit-publication-type-entry">
		<label for="deposit-publication-type">Publication Type</label>
			<input type="radio" name="deposit-publication-type" value="book-chapter" <?php if ( ! empty( $prev_val['deposit-publication-type'] ) ) { checked( sanitize_text_field( $prev_val['deposit-publication-type'] ), 'book-chapter' ); } ?>>Book chapter &nbsp;
			<input type="radio" name="deposit-publication-type" value="journal-article" <?php if ( ! empty( $prev_val['deposit-publication-type'] ) ) { checked( sanitize_text_field( $prev_val['deposit-publication-type'] ), 'journal-article' ); } ?>>Journal article &nbsp;
			<input type="radio" name="deposit-publication-type" value="proceedings-article" <?php if ( ! empty( $prev_val['deposit-publication-type'] ) ) { checked( sanitize_text_field( $prev_val['deposit-publication-type'] ), 'proceedings-article' ); } ?>>Conference proceeding &nbsp;
			<input type="radio" name="deposit-publication-type" value="none" <?php if ( ! empty( $prev_val['deposit-publication-type'] ) ) { checked( sanitize_text_field( $prev_val['deposit-publication-type'] ), 'none' ); } else { echo 'checked="checked"'; } ?>>Not published &nbsp;
	</div>
	</p>
	<div id="deposit-book-entries">

		<div id="deposit-book-doi-entry">
			<label for="deposit-book-doi">Publisher DOI</label>
			<input type="text" id="deposit-book-doi" name="deposit-book-doi" class="long" value="<?php if ( ! empty( $prev_val['deposit-book-doi'] ) ) { echo sanitize_text_field( $prev_val['deposit-book-doi'] ); } ?>" />
		</div>

		<div id="deposit-book-publisher-entry">
			<label for="deposit-book-publisher">Publisher</label>
			<input type="text" id="deposit-book-publisher" name="deposit-book-publisher" size="40" class="long" value="<?php if ( ! empty( $prev_val['deposit-book-publisher'] ) ) { echo sanitize_text_field( $prev_val['deposit-book-publisher'] ); } ?>" />
		</div>

		<div id="deposit-book-publish-date-entry">
			<label for="deposit-book-publish-date">Pub Date</label>
			<input type="text" id="deposit-book-publish-date" name="deposit-book-publish-date" class="text" value="<?php if ( ! empty( $prev_val['deposit-book-publish-date'] ) ) { echo sanitize_text_field( $prev_val['deposit-book-publish-date'] ); } ?>" />
		</div>

		<div id="deposit-book-title-entry">
			<label for="deposit-book-title">Book Title</label>
			<input type="text" id="deposit-book-title" name="deposit-book-title" size="60" class="long" value="<?php if ( ! empty( $prev_val['deposit-book-title'] ) ) { echo sanitize_text_field( $prev_val['deposit-book-title'] ); } ?>" />
		</div>

		<div id="deposit-book-author-entry">
			<label for="deposit-book-author">Book Author or Editor</label>
			<input type="text" id="deposit-book-author" name="deposit-book-author" class="long" value="<?php if ( ! empty( $prev_val['deposit-book-author'] ) ) { echo sanitize_text_field( $prev_val['deposit-book-author'] ); } ?>" />
		</div>

		<div id="deposit-book-chapter-entry">
			<label for="deposit-book-chapter">Chapter</label>
			<input type="text" id="deposit-book-chapter" name="deposit-book-chapter" class="text" value="<?php if ( ! empty( $prev_val['deposit-book-chapter'] ) ) { echo sanitize_text_field( $prev_val['deposit-book-chapter'] ); } ?>" />
		</div>

		<div id="deposit-book-pages-entry">
			<label for="deposit-book-start-page"><span>Start Page</span>
			<input type="text" id="deposit-book-start-page" name="deposit-book-start-page" size="5" class="text" value="<?php if ( ! empty( $prev_val['deposit-book-start-page'] ) ) { echo sanitize_text_field( $prev_val['deposit-book-start-page'] ); } ?>" />
			</label>
			<label for="deposit-book-end-page"><span>End Page</span>
			<input type="text" id="deposit-book-end-page" name="deposit-book-end-page" size="5" class="text" value="<?php if ( ! empty( $prev_val['deposit-book-end-page'] ) ) { echo sanitize_text_field( $prev_val['deposit-book-end-page'] ); } ?>" />
			</label>
			<br style='clear:both'>
		</div>

		<div id="deposit-book-isbn-entry">
			<label for="deposit-book-isbn">ISBN</label>
			<input type="text" id="deposit-book-isbn" name="deposit-book-isbn" class="text" value="<?php if ( ! empty( $prev_val['deposit-book-isbn'] ) ) { echo sanitize_text_field( $prev_val['deposit-book-isbn'] ); } ?>" />
		</div>

	</div>

	<div id="deposit-journal-entries">

		<div id="deposit-journal-doi-entry">
			<label for="deposit-journal-doi">Publisher DOI</label>
			<input type="text" id="deposit-journal-doi" name="deposit-journal-doi" class="long" value="<?php if ( ! empty( $prev_val['deposit-journal-doi'] ) ) { echo sanitize_text_field( $prev_val['deposit-journal-doi'] ); } ?>" />
		</div>

		<div id="deposit-journal-publisher-entry">
			<label for="deposit-journal-publisher">Publisher</label>
			<input type="text" id="deposit-journal-publisher" name="deposit-journal-publisher" size="40" class="long" value="<?php if ( ! empty( $prev_val['deposit-journal-publisher'] ) ) { echo sanitize_text_field( $prev_val['deposit-journal-publisher'] ); } ?>" />
		</div>

		<div id="deposit-journal-publish-date-entry">
			<label for="deposit-journal-publish-date">Pub Date</label>
			<input type="text" id="deposit-journal-publish-date" name="deposit-journal-publish-date" class="text" value="<?php if ( ! empty( $prev_val['deposit-journal-publish-date'] ) ) { echo sanitize_text_field( $prev_val['deposit-journal-publish-date'] ); } ?>" />
		</div>

		<div id="deposit-journal-title-entry">
			<label for="deposit-journal-title">Journal Title</label>
			<input type="text" id="deposit-journal-title" name="deposit-journal-title" size="75" class="long" value="<?php if ( ! empty( $prev_val['deposit-journal-title'] ) ) { echo sanitize_text_field( $prev_val['deposit-journal-title'] ); } ?>" />
		</div>

		<div id="deposit-journal-volume-entry">
			<label for="deposit-journal-volume"><span>Volume</span>
			<input type="text" id="deposit-journal-volume" name="deposit-journal-volume" class="text" value="<?php if ( ! empty( $prev_val['deposit-journal-volume'] ) ) { echo sanitize_text_field( $prev_val['deposit-journal-volume'] ); } ?>" />
			</label>
			<label for="deposit-journal-issue"><span>Issue</span>
			<input type="text" id="deposit-journal-issue" name="deposit-journal-issue" class="text" value="<?php if ( ! empty( $prev_val['deposit-journal-volume'] ) ) { echo sanitize_text_field( $prev_val['deposit-journal-volume'] ); } ?>" />
			</label>
			<br style='clear:both'>
		</div>

		<div id="deposit-journal-pages-entry">
			<label for="deposit-journal-start-page"><span>Start Page</span>
			<input type="text" id="deposit-journal-start-page" name="deposit-journal-start-page" size="5" class="text" value="<?php if ( ! empty( $prev_val['deposit-journal-start-page'] ) ) { echo sanitize_text_field( $prev_val['deposit-journal-start-page'] ); } ?>" />
			</label>
			<label for="deposit-journal-end-page"><span>End Page</span>
			<input type="text" id="deposit-journal-end-page" name="deposit-journal-end-page" size="5" class="text" value="<?php if ( ! empty( $prev_val['deposit-journal-start-page'] ) ) { echo sanitize_text_field( $prev_val['deposit-journal-start-page'] ); } ?>" />
			</label>
			<br style='clear:both'>
		</div>

		<div id="deposit-journal-issn-entry">
			<label for="deposit-journal-issn">ISSN</label>
			<input type="text" id="deposit-journal-issn" name="deposit-journal-issn" class="text" value="<?php if ( ! empty( $prev_val['deposit-journal-issn'] ) ) { echo sanitize_text_field( $prev_val['deposit-journal-issn'] ); } ?>" />
		</div>

	</div>

	<div id="deposit-proceedings-entries">

		<div id="deposit-proceeding-doi-entry">
			<label for="deposit-proceeding-doi">Publisher DOI</label>
			<input type="text" id="deposit-proceeding-doi" name="deposit-proceeding-doi" class="long" value="<?php if ( ! empty( $prev_val['deposit-proceeding-doi'] ) ) { echo sanitize_text_field( $prev_val['deposit-proceeding-doi'] ); } ?>" />
		</div>

		<div id="deposit-proceeding-publisher-entry">
			<label for="deposit-proceeding-publisher">Publisher</label>
			<input type="text" id="deposit-proceeding-publisher" name="deposit-proceeding-publisher" size="40" class="long" value="<?php if ( ! empty( $prev_val['deposit-proceeding-publisher'] ) ) { echo sanitize_text_field( $prev_val['deposit-proceeding-publisher'] ); } ?>" />
		</div>

		<div id="deposit-proceeding-publish-date-entry">
			<label for="deposit-proceeding-publish-date">Pub Date</label>
			<input type="text" id="deposit-proceeding-publish-date" name="deposit-proceeding-publish-date" class="text" value="<?php if ( ! empty( $prev_val['deposit-proceeding-publish-date'] ) ) { echo sanitize_text_field( $prev_val['deposit-proceeding-publish-date'] ); } ?>" />
		</div>

		<div id="deposit-proceeding-title-entry">
			<label for="deposit-proceeding-title">Proceeding Title</label>
			<input type="text" id="deposit-proceeding-title" name="deposit-proceeding-title" size="75" class="long" value="<?php if ( ! empty( $prev_val['deposit-proceeding-title'] ) ) { echo sanitize_text_field( $prev_val['deposit-proceeding-title'] ); } ?>" />
		</div>

		<div id="deposit-proceeding-pages-entry">
			<label for="deposit-proceeding-start-page"><span>Start Page</span>
			<input type="text" id="deposit-proceeding-start-page" name="deposit-proceeding-start-page" size="5" class="text" value="<?php if ( ! empty( $prev_val['deposit-proceeding-start-page'] ) ) { echo sanitize_text_field( $prev_val['deposit-proceeding-start-page'] ); } ?>" />
			</label>
			<label for="deposit-proceeding-end-page"><span>End Page</span>
			<input type="text" id="deposit-proceeding-end-page" name="deposit-proceeding-end-page" size="5" class="text" value="<?php if ( ! empty( $prev_val['deposit-proceeding-start-page'] ) ) { echo sanitize_text_field( $prev_val['deposit-proceeding-start-page'] ); } ?>" />
			</label>
			<br style='clear:both'>
		</div>

	</div>

	<div id="deposit-non-published-entries">

		<div id="deposit-non-published-date-entry">
			<label for="deposit-non-published-date">Date of Creation</label>
			<input type="text" id="deposit-non-published-date" name="deposit-non-published-date" class="text" value="<?php if ( ! empty( $prev_val['deposit-non-published-date'] ) ) { echo sanitize_text_field( $prev_val['deposit-non-published-date'] ); } ?>" />
		</div>

	</div>
	<p>
	<div id="deposit-license-type-entry">
		<label for="deposit-license-type">Creative Commons License</label>
		<span class="description">By default, and in accordance with section 2 of the <em>Commons</em> terms of service, no one may reuse this content in any way. Should you wish to allow others to distribute, display, modify, or otherwise reuse your content, please attribute it with the appropriate Creative Commons license from the drop-down menu below. See <a onclick="target='_blank'" href="http://creativecommons.org/licenses/">this page</a> for more information about the different types of Creative Commons licenses.</span><br /><br />
		<select name="deposit-license-type" id="deposit-license-type" class="js-basic-single-required">
<?php
	$license_type_list = humcore_deposits_license_type_list();
	$posted_license_type = '';
	if ( ! empty( $prev_val['deposit-license-type'] ) ) {
		$posted_license_type = sanitize_text_field( $prev_val['deposit-license-type'] );
	}
	foreach ( $license_type_list as $license_key => $license_value ) {
		printf('			<option class="level-1" %1$s value="%2$s">%3$s</option>' . "\n",
			( $license_key == $posted_license_type ) ? 'selected="selected"' : '',
			$license_key,
			$license_value
		);
	}
?>
		</select>
		<span class="description">*</span>
	</div>
	</p>
        <p>
        <div id="deposit-embargoed-entry">
                <label for="deposit-embargoed-flag">Embargo this deposit?</label>
                        <input type="radio" name="deposit-embargoed-flag" value="yes" <?php if ( ! empty( $prev_val['deposit-embargoed-flag'] ) ) { checked( sanitize_text_field( $prev_val['deposit-embargoed-flag'] ), 'yes' ); } ?>>Yes &nbsp;
                        <input type="radio" name="deposit-embargoed-flag" value="no" <?php if ( ! empty( $prev_val['deposit-embargoed-flag'] ) ) { checked( sanitize_text_field( $prev_val['deposit-embargoed-flag'] ), 'no' ); } else { echo 'checked="checked"'; } ?>>No &nbsp;
        </div>

	<div id="deposit-embargoed-entries">
        <label for="deposit-embargo-length">Embargo Length</label>
        <div id="deposit-embargo-length-entry">
                <span class="description">Use <a onclick="target='_blank'" href="http://www.sherpa.ac.uk/romeo/">SHERPA/RoMEO</a> to check a journal’s open access policies.</span><br />
		<span class="description">Enter the length of time (up to two years from now) after which this item should become available.</span> <br />
                <select name="deposit-embargo-length" id="deposit-embargo-length" class="js-basic-single-required" data-placeholder="Select the embargo length." data-allowClear="true">
                        <option class="level-0" selected="selected" value=""></option>

<?php
        $embargo_length_list = humcore_deposits_embargo_length_list();
        $posted_embargo_length = '';
        if ( ! empty( $prev_val['deposit-embargo-length'] ) ) {
                $posted_embargo_length = sanitize_text_field( $prev_val['deposit-embargo-length'] );
        }
        foreach ( $embargo_length_list as $embargo_key => $embargo_value ) {
                printf('                        <option class="level-0" %1$s value="%2$s">%3$s</option>' . "\n",
                        ( $embargo_key == $posted_embargo_length ) ? 'selected="selected"' : '',
                        $embargo_key,
                        $embargo_value
                );
        }
?>
                </select>
		<span class="description">*</span>
        </div>
	</div>
        </p>

	<input id="deposit-submit" name="deposit-submit" type="submit" value="Deposit" />
	<?php $wp_referer = wp_get_referer();
		printf(
			'<a id="deposit-cancel" href="%1$s" class="button white">Cancel</a>',
			( ! empty( $wp_referer ) && ! strpos( $wp_referer, 'item/new' ) ) ? $wp_referer : '/deposits/'
		);
	?>

	</div>

</form>
	<br /><span class="description">Required fields are marked *.</span><br />
<br />

<div id="deposit-warning-dialog">
</div>
<div id="deposit-error-dialog">
</div>

<?php

}

/**
 * Output deposits list entry html.
 */
function humcore_deposits_list_entry_content() {

	$metadata = (array) humcore_get_current_deposit();
        $authors = array_filter( $metadata['authors'] );
        $authors_list = implode( ', ', $authors );

	$item_url = sprintf( '%1$s/deposits/item/%2$s', bp_get_root_domain(), $metadata['pid'] );
?>
<ul class="deposits-item">
<li>
<span class="list-item-label">Title</span>
<span class="list-item-value"><?php echo $metadata['title']; ?></span>
</li>
<li>
<span class="list-item-label">URL</span>
<span class="list-item-value"><a href="<?php echo esc_url( $item_url ); ?>"><?php echo esc_url( $item_url ); ?></a></span>
</li>
<li>
<span class="list-item-label">Author(s)</span>
<span class="list-item-value"><?php echo esc_html( $authors_list ); ?></span>
</li>
<li>
<span class="list-item-label">Date</span>
<span class="list-item-value"><?php echo esc_html( $metadata['date'] ); ?></span>
</li>
</ul>
<?php

}

/**
 * Output deposits feed item html.
 */
function humcore_deposits_feed_item_content() {

	$metadata = (array) humcore_get_current_deposit();

        $contributors = array_filter( $metadata['authors'] );
        $contributor_uni = humcore_deposit_parse_author_info( $metadata['author_info'][0], 1 );
        $contributor_type = humcore_deposit_parse_author_info( $metadata['author_info'][0], 3 );
        $contributors_list = array_map( null, $contributors, $contributor_uni, $contributor_type );
        $authors_list = array();
        $authors_list = '';
        foreach( $contributors_list as $contributor ) {
                if ( 'author' === $contributor[2] || empty( $contributor[2] ) ) {
			$authors_list .= "\t\t" . sprintf( '<dc:creator>%s</dc:creator>', htmlspecialchars( $contributor[0], ENT_QUOTES ) );
                }
        }

        foreach ( $authors as $author ) {
	}

	$item_url = sprintf( '%1$s/deposits/item/%2$s', bp_get_root_domain(), $metadata['pid'] );
	$pub_date = DateTime::createFromFormat( 'Y-m-d\TH:i:s\Z', $metadata['record_creation_date'] );
?>
		<title><?php echo htmlspecialchars( $metadata['title'], ENT_QUOTES ); ?></title>
		<link><?php echo esc_url( $item_url ); ?></link>
		<pubDate><?php echo $pub_date->format( 'D, d M Y H:i:s +0000' ); ?></pubDate>
		<?php echo $authors_list; ?>
		<guid isPermaLink="false"><?php echo esc_url( $item_url ); ?></guid>
		<description><![CDATA[<?php echo $metadata['abstract']; ?>]]></description>
<?php

}

/**
 * Output deposits loop entry html.
 */
function humcore_deposits_entry_content() {

	$metadata = (array) humcore_get_current_deposit();

	if ( ! empty( $metadata['group'] ) ) {
		$groups = array_filter( $metadata['group'] );
	}
	if ( ! empty( $groups ) ) {
		$group_list = implode( ', ', array_map( 'humcore_linkify_group', $groups ) );
	}
	if ( ! empty( $metadata['subject'] ) ) {
		$subjects = array_filter( $metadata['subject'] );
	}
	if ( ! empty( $subjects ) ) {
		$subject_list = implode( ', ', array_map( 'humcore_linkify_subject', $subjects ) );
	}
        if ( ! empty( $metadata['keyword'] ) ) {
                $keywords = array_filter( $metadata['keyword'] );
                $keyword_display_values = array_filter( explode( ', ', $metadata['keyword_display'] ) );
        }
        if ( ! empty( $keywords ) ) {
                $keyword_list = implode( ', ', array_map( 'humcore_linkify_tag', $keywords, $keyword_display_values ) );
        }

	$contributors = array_filter( $metadata['authors'] );
	$contributor_uni = humcore_deposit_parse_author_info( $metadata['author_info'][0], 1 );
	$contributor_type = humcore_deposit_parse_author_info( $metadata['author_info'][0], 3 );
	$contributors_list = array_map( null, $contributors, $contributor_uni, $contributor_type );
	$authors_list = array();
	$editors_list = array();
	$translators_list = array();
	$project_directors_list = array();
	foreach( $contributors_list as $contributor ) {
		if ( 'author' === $contributor[2] || empty( $contributor[2] ) ) {
			$authors_list[] = humcore_linkify_author( $contributor[0], $contributor[1], $contributor[2] );
		} else if ( 'editor' === $contributor[2] ) {
                        $editors_list[] = humcore_linkify_author( $contributor[0], $contributor[1], $contributor[2] );
		} else if ( 'project director' === $contributor[2] ) {
                        $project_directors_list[] = humcore_linkify_author( $contributor[0], $contributor[1], $contributor[2] );
		} else if ( 'translator' === $contributor[2] ) {
                        $translators_list[] = humcore_linkify_author( $contributor[0], $contributor[1], $contributor[2] );
		}
	}
	$item_url = sprintf( '%1$s/deposits/item/%2$s', bp_get_root_domain(), $metadata['pid'] );
?>
<h4 class="bp-group-documents-title"><a href="<?php echo esc_url( $item_url ); ?>/"><?php echo $metadata['title_unchanged']; ?></a></h4>
<div class="bp-group-documents-meta">
<dl class='defList'>
<?php if ( ! empty( $project_directors_list ) ) : ?>
<dt><?php _e( 'Project Director(s):', 'humcore_domain' ); ?></dt>
<dd><?php echo implode( ', ', $project_directors_list ); // XSS OK. ?></dd>
<?php endif; ?>
<?php if ( ! empty( $authors_list ) ) : ?>
<dt><?php _e( 'Author(s):', 'humcore_domain' ); ?></dt>
<dd><?php echo implode( ', ', $authors_list ); // XSS OK. ?></dd>
<?php endif; ?>
<?php if ( ! empty( $editors_list ) ) : ?>
<dt><?php _e( 'Editor(s):', 'humcore_domain' ); ?></dt>
<dd><?php echo implode( ', ', $editors_list ); // XSS OK. ?></dd>
<?php endif; ?>
<?php if ( ! empty( $translators_list ) ) : ?>
<dt><?php _e( 'Translator(s):', 'humcore_domain' ); ?></dt>
<dd><?php echo implode( ', ', $translators_list ); // XSS OK. ?></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['date'] ) ) : ?>
<dt><?php _e( 'Date:', 'humcore_domain' ); ?></dt>
<dd><a href="/deposits/?facets[pub_date_facet][]=<?php echo urlencode( $metadata['date'] ); ?>"><?php echo esc_html( $metadata['date'] ); ?></a></dd>
<?php endif; ?>
<?php if ( ! empty( $groups ) ) : ?>
<dt><?php _e( 'Group(s):', 'humcore_domain' ); ?></dt>
<dd><?php echo $group_list; // XSS OK. ?></dd>
<?php endif; ?>
<?php if ( ! empty( $subjects ) ) : ?>
<dt><?php _e( 'Subject(s):', 'humcore_domain' ); ?></dt>
<dd><?php echo $subject_list; // XSS OK. ?></dd>
<?php endif; ?>

<?php if ( ! empty( $metadata['genre'] ) ) : ?>
<dt><?php _e( 'Item Type:', 'humcore_domain' ); ?></dt>
<dd><a href="/deposits/?facets[genre_facet][]=<?php echo urlencode( $metadata['genre'] ); ?>"><?php echo esc_html( $metadata['genre'] ); ?></a></dd>
<?php endif; ?>
<?php if ( ! empty( $keywords ) ) : ?>
<dt><?php _e( 'Tag(s):', 'humcore_domain' ); ?></dt>
<dd><?php echo $keyword_list; // XSS OK. ?></dd>
<?php endif; ?>
<dt><?php _e( 'Permanent URL:', 'humcore_domain' ); ?></dt>
<dd><a href="<?php echo esc_attr( $item_url ); ?>"><?php echo esc_html( $metadata['handle'] ); ?></a></dd>
</dl>
</div>
<br style='clear:both'>
<?php

}

/**
 * Output deposits single item html.
 */
function humcore_deposit_item_content() {

	$metadata = (array) humcore_get_current_deposit();

	if ( ! empty( $metadata['group'] ) ) {
		$groups = array_filter( $metadata['group'] );
	}
	if ( ! empty( $groups ) ) {
		$group_list = implode( ', ', array_map( 'humcore_linkify_group', $groups ) );
	}
	if ( ! empty( $metadata['subject'] ) ) {
		$subjects = array_filter( $metadata['subject'] );
	}
	if ( ! empty( $subjects ) ) {
		$subject_list = implode( ', ', array_map( 'humcore_linkify_subject', $subjects ) );
	}
        if ( ! empty( $metadata['keyword'] ) ) {
                $keywords = array_filter( $metadata['keyword'] );
                $keyword_display_values = array_filter( explode( ', ', $metadata['keyword_display'] ) );
        }
        if ( ! empty( $keywords ) ) {
                $keyword_list = implode( ', ', array_map( 'humcore_linkify_tag', $keywords, $keyword_display_values ) );
        }

        $contributors = array_filter( $metadata['authors'] );
        $contributor_uni = humcore_deposit_parse_author_info( $metadata['author_info'][0], 1 );
        $contributor_type = humcore_deposit_parse_author_info( $metadata['author_info'][0], 3 );
        $contributors_list = array_map( null, $contributors, $contributor_uni, $contributor_type );
        $authors_list = array();
        $editors_list = array();
        $translators_list = array();
        $project_directors_list = array();
        foreach( $contributors_list as $contributor ) {
                if ( 'author' === $contributor[2] || empty( $contributor[2] ) ) {
                        $authors_list[] = humcore_linkify_author( $contributor[0], $contributor[1], $contributor[2] );
                } else if ( 'editor' === $contributor[2] ) {
                        $editors_list[] = humcore_linkify_author( $contributor[0], $contributor[1], $contributor[2] );
                } else if ( 'project director' === $contributor[2] ) {
                        $project_directors_list[] = humcore_linkify_author( $contributor[0], $contributor[1], $contributor[2] );
                } else if ( 'translator' === $contributor[2] ) {
                        $translators_list[] = humcore_linkify_author( $contributor[0], $contributor[1], $contributor[2] );
                }
        }

        $wpmn_record_identifier = array();
        $wpmn_record_identifier = explode( '-', $metadata['record_identifier'] );
	// handle legacy MLA value
	if ( $wpmn_record_identifier[0] === $metadata['record_identifier'] ) {
		$wpmn_record_identifier[0] = '1';
		$wpmn_record_identifier[1] = $metadata['record_identifier'];
	}
        $switched = false;
        if ( $wpmn_record_identifier[0] != get_current_blog_id() ) {
                switch_to_blog( $wpmn_record_identifier[0] );
                $switched = true;
        }

	$site_url = get_option( 'siteurl' );
	$deposit_post_id = $wpmn_record_identifier[1];
	$post_data = get_post( $deposit_post_id );
	$post_metadata = json_decode( get_post_meta( $deposit_post_id, '_deposit_metadata', true ), true );

	$update_time = '';
	if ( ! empty( $metadata['record_change_date'] ) ) {
		$update_time = human_time_diff( strtotime( $metadata['record_change_date'] ) );
	}
	$file_metadata = json_decode( get_post_meta( $deposit_post_id, '_deposit_file_metadata', true ), true );
	$content_downloads_meta_key = sprintf( '_total_downloads_%s_%s', $file_metadata['files'][0]['datastream_id'], $file_metadata['files'][0]['pid'] );
	$total_content_downloads = get_post_meta( $deposit_post_id, $content_downloads_meta_key, true );
	$content_views_meta_key = sprintf( '_total_views_%s_%s', $file_metadata['files'][0]['datastream_id'], $file_metadata['files'][0]['pid'] );
	$total_content_views = get_post_meta( $deposit_post_id, $content_views_meta_key, true );
	$views_meta_key = sprintf( '_total_views_%s', $metadata['pid'] );
	$total_views = get_post_meta( $deposit_post_id, $views_meta_key, true ) + 1; // Views counted at item page level.
	if ( $post_data->post_author != bp_loggedin_user_id() && ! humcore_is_bot_user_agent() ) {
		$post_meta_ID = update_post_meta( $deposit_post_id, $views_meta_key, $total_views );
	}
	$download_url = sprintf( '%s/deposits/download/%s/%s/%s/',
		$site_url,
		$file_metadata['files'][0]['pid'],
		$file_metadata['files'][0]['datastream_id'],
		$file_metadata['files'][0]['filename']
	);
	$view_url = sprintf( '%s/deposits/view/%s/%s/%s/',
		$site_url,
		$file_metadata['files'][0]['pid'],
		$file_metadata['files'][0]['datastream_id'],
		$file_metadata['files'][0]['filename']
	);
	$metadata_url = sprintf( '%s/deposits/download/%s/%s/%s/',
		$site_url,
		$metadata['pid'],
		'descMetadata',
		'xml'
	);
	$file_type_data = wp_check_filetype( $file_metadata['files'][0]['filename'], wp_get_mime_types() );
	$file_type_icon = sprintf( '<img class="deposit-icon" src="%s" alt="%s" />',
		plugins_url( 'assets/' . esc_attr( $file_type_data['ext'] ) . '-icon-48x48.png', __FILE__ ),
		esc_attr( $file_type_data['ext'] )
	);
	if ( in_array( $file_type_data['type'], array( 'application/pdf', 'text/html', 'text/plain' ) ) ||
		in_array( strstr( $file_type_data['type'], '/', true ), array( 'audio', 'image', 'video' ) ) ) {
		$content_viewable = true;
	} else {
		$content_viewable = false;
	}

	if ( ! empty( $file_metadata['files'][0]['thumb_filename'] ) ) {
		$thumb_url = sprintf( '<img class="deposit-thumb" src="%s/deposits/view/%s/%s/%s/" alt="%s" />',
			$site_url,
			$file_metadata['files'][0]['pid'],
			$file_metadata['files'][0]['thumb_datastream_id'],
			$file_metadata['files'][0]['thumb_filename'],
			'thumbnail'
		);
	} else {
		$thumb_url = '';
	}
        if ( $switched ) {
                restore_current_blog();
        }
	$item_url = sprintf( '%1$s/deposits/item/%2$s', bp_get_root_domain(), $metadata['pid'] );
?>

<h3 class="bp-group-documents-title"><?php echo $metadata['title_unchanged']; ?></h3>
<div class="bp-group-documents-meta">
<dl class='defList'>
<?php if ( ! empty( $project_directors_list ) ) : ?>
<dt><?php _e( 'Project Director(s):', 'humcore_domain' ); ?></dt>
<dd><?php echo implode( ', ', $project_directors_list ); // XSS OK. ?></dd>
<?php endif; ?>
<?php if ( ! empty( $authors_list ) ) : ?>
<dt><?php _e( 'Author(s):', 'humcore_domain' ); ?></dt>
<dd><?php echo implode( ', ', $authors_list ); // XSS OK. ?></dd>
<?php endif; ?>
<?php if ( ! empty( $editors_list ) ) : ?>
<dt><?php _e( 'Editor(s):', 'humcore_domain' ); ?></dt>
<dd><?php echo implode( ', ', $editors_list ); // XSS OK. ?></dd>
<?php endif; ?>
<?php if ( ! empty( $translators_list ) ) : ?>
<dt><?php _e( 'Translator(s):', 'humcore_domain' ); ?></dt>
<dd><?php echo implode( ', ', $translators_list ); // XSS OK. ?></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['date'] ) ) : ?>
<dt><?php _e( 'Date:', 'humcore_domain' ); ?></dt>
<dd><a href="/deposits/?facets[pub_date_facet][]=<?php echo urlencode( $metadata['date'] ); ?>"><?php echo esc_html( $metadata['date'] ); ?></a></dd>
<?php endif; ?>
<?php if ( ! empty( $groups ) ) : ?>
<dt><?php _e( 'Group(s):', 'humcore_domain' ); ?></dt>
<dd><?php echo $group_list; // XSS OK. ?></dd>
<?php endif; ?>
<?php if ( ! empty( $subjects ) ) : ?>
<dt><?php _e( 'Subject(s):', 'humcore_domain' ); ?></dt>
<dd><?php echo $subject_list; // XSS OK. ?></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['genre'] ) ) : ?>
<dt><?php _e( 'Item Type:', 'humcore_domain' ); ?></dt>
<dd><a href="/deposits/?facets[genre_facet][]=<?php echo urlencode( $metadata['genre'] ); ?>"><?php echo esc_html( $metadata['genre'] ); ?></a></dd>
<?php endif; ?>
<?php if ( 'Conference paper' == $metadata['genre'] || 'Conference proceeding' == $metadata['genre'] ) : ?>
<?php if ( ! empty( $metadata['conference_title'] ) ) : ?>
<dt><?php _e( 'Conf. Title:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['conference_title']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['conference_organization'] ) ) : ?>
<dt><?php _e( 'Conf. Org.:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['conference_organization']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['conference_location'] ) ) : ?>
<dt><?php _e( 'Conf. Loc.:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['conference_location']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['conference_date'] ) ) : ?>
<dt><?php _e( 'Conf. Date:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['conference_date']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php elseif ( 'Presentation' == $metadata['genre'] ) : ?>
<?php if ( ! empty( $metadata['meeting_title'] ) ) : ?>
<dt><?php _e( 'Meeting Title:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['meeting_title']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['meeting_organization'] ) ) : ?>
<dt><?php _e( 'Meeting Org.:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['meeting_organization']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['meeting_location'] ) ) : ?>
<dt><?php _e( 'Meeting Loc.:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['meeting_location']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['meeting_date'] ) ) : ?>
<dt><?php _e( 'Meeting Date:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['meeting_date']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php elseif ( 'Dissertation' == $metadata['genre'] || 'Technical report' == $metadata['genre'] || 'Thesis' == $metadata['genre'] ||
		 'White paper' == $metadata['genre'] ) : ?>
<?php if ( ! empty( $metadata['institution'] ) ) : ?>
<dt><?php _e( 'Institution:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['institution']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php endif; ?>
<?php if ( ! empty( $keywords ) ) : ?>
<dt><?php _e( 'Tag(s):', 'humcore_domain' ); ?></dt>
<dd><?php echo $keyword_list; // XSS OK. ?></dd>
<?php endif; ?>
<dt><?php _e( 'Permanent URL:', 'humcore_domain' ); ?></dt>
<dd><a href="<?php echo esc_attr( $item_url ); ?>"><?php echo esc_html( $metadata['handle'] ); ?></a></dd>
<dt><?php _e( 'Abstract:', 'humcore_domain' ); // Google Scholar wants Abstract. ?></dt>
<?php if ( ! empty( $metadata['abstract_unchanged'] ) ) : ?>
<dd><?php echo $metadata['abstract_unchanged']; ?></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['notes_unchanged'] ) ) : ?>
<dt><?php _e( 'Notes:', 'humcore_domain' ); ?></dt>
<dd><?php echo $metadata['notes_unchanged']; ?></dd>
<?php endif; ?>
<dt><?php _e( 'Metadata:', 'humcore_domain' ); ?></dt>
<dd><a onclick="target='_blank'" class="bp-deposits-metadata" title="MODS Metadata" rel="nofollow" href="<?php echo esc_url( $metadata_url ); ?>">xml</a></dd>
<?php if ( 'journal-article' == $post_metadata['publication-type'] ) : ?>
<dt><?php _e( 'Published as:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo 'Journal Article'; // XSS OK. ?></span></dd>
<?php if ( ! empty( $metadata['doi'] ) ) : ?>
<dt><?php _e( 'Pub. DOI:', 'humcore_domain' ); ?></dt>
<?php endif; ?>
<?php if ( ! empty( $metadata['doi'] ) ) : ?>
<dd><span><?php echo $metadata['doi']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['publisher'] ) ) : ?>
<dt><?php _e( 'Publisher:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['publisher']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['book_journal_title'] ) ) : ?>
<dt><?php _e( 'Journal:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['book_journal_title']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['volume'] ) ) : ?>
<dt><?php _e( 'Volume:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['volume']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['issue'] ) ) : ?>
<dt><?php _e( 'Issue:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['issue']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['start_page'] ) ) : ?>
<dt><?php _e( 'Start Page:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['start_page']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['end_page'] ) ) : ?>
<dt><?php _e( 'End Page:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['end_page']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['issn'] ) ) : ?>
<dt><?php _e( 'ISSN:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['issn']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php elseif ( 'book-chapter' == $post_metadata['publication-type'] ) : ?>
<dt><?php _e( 'Published as:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo 'Book chapter'; // XSS OK. ?></span></dd>
<?php if ( ! empty( $metadata['doi'] ) ) : ?>
<dt><?php _e( 'Pub. DOI:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['doi']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['publisher'] ) ) : ?>
<dt><?php _e( 'Publisher:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['publisher']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['book_author'] ) ) : ?>
<dt><?php _e( 'Author/Editor:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['book_author']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['book_journal_title'] ) ) : ?>
<dt><?php _e( 'Book Title:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['book_journal_title']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['chapter'] ) ) : ?>
<dt><?php _e( 'Chapter:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['chapter']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['start_page'] ) ) : ?>
<dt><?php _e( 'Start Page:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['start_page']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['end_page'] ) ) : ?>
<dt><?php _e( 'End Page:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['end_page']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['isbn'] ) ) : ?>
<dt><?php _e( 'ISBN:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['isbn']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php elseif ( 'proceedings-article' == $post_metadata['publication-type'] ) : ?>
<dt><?php _e( 'Published as:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo 'Proceedings article'; // XSS OK. ?></span></dd>
<?php if ( ! empty( $metadata['doi'] ) ) : ?>
<dt><?php _e( 'Pub. DOI:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['doi']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['publisher'] ) ) : ?>
<dt><?php _e( 'Publisher:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['publisher']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['book_journal_title'] ) ) : ?>
<dt><?php _e( 'Proceeding:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['book_journal_title']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['start_page'] ) ) : ?>
<dt><?php _e( 'Start Page:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['start_page']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['end_page'] ) ) : ?>
<dt><?php _e( 'End Page:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['end_page']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php endif; ?>
<?php if ( 'draft' === $post_data->post_status ) : ?>
<dt><?php _e( 'Status:', 'humcore_domain' ); ?></dt> 
<dd><?php echo '<strong>Provisional</strong>'; ?></dd>
<?php elseif ( 'pending' === $post_data->post_status ) : ?>
<dt><?php _e( 'Status:', 'humcore_domain' ); ?></dt> 
<dd><?php echo 'Pending Review'; ?></dd>
<?php elseif ( 'publish' === $post_data->post_status ) : ?>
<dt><?php _e( 'Status:', 'humcore_domain' ); ?></dt> 
<dd><?php echo 'Published'; ?></dd>
<?php endif; ?>
<?php if ( ! empty( $update_time ) ) : ?>
<dt><?php _e( 'Last Updated:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $update_time . ' ago'; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $thumb_url ) ) : ?>
<dt><?php _e( 'Preview:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $thumb_url;// XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $post_metadata['type_of_license'] ) ) : ?>
<dt><?php _e( 'License:', 'humcore_domain' ); ?></dt>
<dd><?php echo humcore_linkify_license( $post_metadata['type_of_license'] ); ?></dd>
<?php endif; ?>
</dl>
<?php if ( 'yes' === $post_metadata['embargoed'] && current_time( 'Y/m/d' ) < date( 'Y/m/d', strtotime( $post_metadata['embargo_end_date'] ) ) ) { ?>
<div><h4>This item will be available for download beginning <?php echo $post_metadata['embargo_end_date']; ?></h4></div> 
<?php } elseif ( ! $post_data ) { ?>
<div><h3>Note</h3>
There is a problem retrieving some of the data for this item. This error has been logged.
</div>
<?php humcore_write_error_log( 'error', '*****HumCORE Data Error*****', $wpmn_record_identifier ); ?>
<?php } else { ?>
<div><h4><?php _e( 'Downloads', 'humcore_domain' ); ?></h4>
<div class="doc-attachments">
	<table class="view_downloads">
	<tr>
		<td class="prompt"><?php _e( 'Item Name:', 'humcore_domain' ); ?></td>
		<td class="value"><?php echo $file_type_icon . ' ' . esc_attr( $file_metadata['files'][0]['filename'] ); // XSS OK. ?></td>
	</tr>
	<tr>
		<td class="prompt">&nbsp;</td>
		<td class="value"><a class="bp-deposits-download button" title="Download" rel="nofollow" href="<?php echo esc_url( $download_url ); ?>"><?php _e( 'Download', 'humcore_domain' ); ?></a>
<?php if ( $content_viewable ) : ?>
	 	<a onclick="target='_blank'" class="bp-deposits-view button" title="View" rel="nofollow" href="<?php echo esc_url( $view_url ); ?>"><?php _e( 'View in browser', 'humcore_domain' ); ?></a>
<?php endif; ?>
		</td>
	</tr>
	</table>
</div>
<div class="doc-statistics">
	<table class="view_statistics">
	<tr>
		<td class="prompt">Activity:</td>
		<td class="value"><?php _e( 'Downloads:', 'humcore_domain' ); echo ' ' . esc_html( $total_content_downloads + $total_content_views ); ?></td>
	</tr>
	</table>
</div>
</div>
<?php } ?>
</div>
<br style='clear:both'>
<?php

}

/**
 * Output deposits single item review page html.
 */
function humcore_deposit_item_review_content() {

        $metadata = (array) humcore_get_current_deposit();
        if ( ! empty( $metadata['group'] ) ) {
                $groups = array_filter( $metadata['group'] );
        }
        if ( ! empty( $groups ) ) {
                $group_list = implode( ', ', array_map( 'esc_html', $groups ) );
        }
        if ( ! empty( $metadata['subject'] ) ) {
                $subjects = array_filter( $metadata['subject'] );
        }
        if ( ! empty( $subjects ) ) {
                $subject_list = implode( ', ', array_map( 'esc_html', $subjects ) );
        }
        if ( ! empty( $metadata['keyword'] ) ) {
                $keywords = array_filter( $metadata['keyword'] );
                $keyword_display_values = explode( ', ', array_filter( $metadata['keyword_display'] ) );
        }
        if ( ! empty( $keywords ) ) {
                $keyword_list = implode( ', ', array_map( 'esc_html', $keywords, $keyword_display_values ) );
        }

        $contributors = array_filter( $metadata['authors'] );
        $contributor_uni = humcore_deposit_parse_author_info( $metadata['author_info'][0], 1 );
        $contributor_type = humcore_deposit_parse_author_info( $metadata['author_info'][0], 3 );
        $contributors_list = array_map( null, $contributors, $contributor_uni, $contributor_type );
        $authors_list = array();
        $editors_list = array();
        $translators_list = array();
        $project_directors_list = array();
        foreach( $contributors_list as $contributor ) {
                if ( 'author' === $contributor[2] || empty( $contributor[2] ) ) {
                        $authors_list[] = humcore_linkify_author( $contributor[0], $contributor[1], $contributor[2] );
                } else if ( 'editor' === $contributor[2] ) {
                        $editors_list[] = humcore_linkify_author( $contributor[0], $contributor[1], $contributor[2] );
                } else if ( 'project director' === $contributor[2] ) {
                        $project_directors_list[] = humcore_linkify_author( $contributor[0], $contributor[1], $contributor[2] );
                } else if ( 'translator' === $contributor[2] ) {
                        $translators_list[] = humcore_linkify_author( $contributor[0], $contributor[1], $contributor[2] );
                }
        }

        $item_url = sprintf( '%1$s/deposits/item/%2$s', bp_get_root_domain(), $metadata['pid'] );

        $wpmn_record_identifier = array();
        $wpmn_record_identifier = explode( '-', $metadata['record_identifier'] );
        // handle legacy MLA value
        if ( $wpmn_record_identifier[0] === $metadata['record_identifier'] ) {
                $wpmn_record_identifier[0] = '1';
                $wpmn_record_identifier[1] = $metadata['record_identifier'];
        }
        $switched = false;
        if ( $wpmn_record_identifier[0] !== get_current_blog_id() ) {
                switch_to_blog( $wpmn_record_identifier[0] );
                $switched = true;
        }

	$site_url = get_option( 'siteurl' );
        $deposit_post_id = $wpmn_record_identifier[1];
        $post_data = get_post( $deposit_post_id );
        $post_metadata = json_decode( get_post_meta( $deposit_post_id, '_deposit_metadata', true ), true );

	$update_time = '';
	if ( ! empty( $metadata['record_change_date'] ) ) { 
		$update_time = human_time_diff( strtotime( $metadata['record_change_date'] ) );
	}
	$file_metadata = json_decode( get_post_meta( $deposit_post_id, '_deposit_file_metadata', true ), true );
        $content_downloads_meta_key = sprintf( '_total_downloads_%s_%s', $file_metadata['files'][0]['datastream_id'], $file_metadata['files'][0]['pid'] );
        $total_content_downloads = get_post_meta( $deposit_post_id, $content_downloads_meta_key, true );
        $content_views_meta_key = sprintf( '_total_views_%s_%s', $file_metadata['files'][0]['datastream_id'], $file_metadata['files'][0]['pid'] );
        $total_content_views = get_post_meta( $deposit_post_id, $content_views_meta_key, true );
        $views_meta_key = sprintf( '_total_views_%s', $metadata['pid'] );
        $total_views = get_post_meta( $deposit_post_id, $views_meta_key, true ) + 1; // Views counted at item page level.
        if ( $post_data->post_author != bp_loggedin_user_id() && ! humcore_is_bot_user_agent() ) {
                $post_meta_ID = update_post_meta( $deposit_post_id, $views_meta_key, $total_views );
        }
        $download_url = sprintf( '%s/deposits/download/%s/%s/%s/',
		$site_url,
                $file_metadata['files'][0]['pid'],
                $file_metadata['files'][0]['datastream_id'],
                $file_metadata['files'][0]['filename']
        );
        $view_url = sprintf( '%s/deposits/view/%s/%s/%s/',
		$site_url,
                $file_metadata['files'][0]['pid'],
                $file_metadata['files'][0]['datastream_id'],
                $file_metadata['files'][0]['filename']
        );
        $metadata_url = sprintf( '%s/deposits/download/%s/%s/%s/',
		$site_url,
                $metadata['pid'],
                'descMetadata',
                'xml'
        );
        $file_type_data = wp_check_filetype( $file_metadata['files'][0]['filename'], wp_get_mime_types() );
        $file_type_icon = sprintf( '<img class="deposit-icon" src="%s" alt="%s" />',
                plugins_url( 'assets/' . esc_attr( $file_type_data['ext'] ) . '-icon-48x48.png', __FILE__ ),
                esc_attr( $file_type_data['ext'] )
        );
        if ( ! empty( $file_metadata['files'][0]['thumb_filename'] ) ) {
                $thumb_url = sprintf( '<img class="deposit-thumb" src="%s/deposits/view/%s/%s/%s/" alt="%s" />',
			$site_url,
                        $file_metadata['files'][0]['pid'],
                        $file_metadata['files'][0]['thumb_datastream_id'],
                        $file_metadata['files'][0]['thumb_filename'],
                        'thumbnail'
                );
        } else {
                $thumb_url = '';
        }
        if ( $switched ) {
                restore_current_blog();
        }
?>

<div class="bp-group-documents-meta">
<dl class='defList'>
<dt><?php _e( 'Title:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['title_unchanged']; // XSS OK. ?></span></dd>
<dt><?php _e( 'Item Type:', 'humcore_domain' ); ?></dt>
<dd><?php echo esc_html( $metadata['genre'] ); ?></dd>
<!-- //new stuff -->
<?php if ( 'Conference paper' == $metadata['genre'] || 'Conference proceeding' == $metadata['genre'] ) : ?>
<dt><?php _e( 'Conf. Title:', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $metadata['conference_title'] ) ) : ?>
<dd><span><?php echo $metadata['conference_title']; // XSS OK. ?></span></dd>
<?php endif; ?>
<dt><?php _e( 'Conf. Org.:', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $metadata['conference_organization'] ) ) : ?>
<dd><span><?php echo $metadata['conference_organization']; // XSS OK. ?></span></dd>
<?php endif; ?>
<dt><?php _e( 'Conf. Loc.:', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $metadata['conference_location'] ) ) : ?>
<dd><span><?php echo $metadata['conference_location']; // XSS OK. ?></span></dd>
<?php endif; ?>
<dt><?php _e( 'Conf. Date:', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $metadata['conference_date'] ) ) : ?>
<dd><span><?php echo $metadata['conference_date']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php elseif ( 'Presentation' == $metadata['genre'] ) : ?>
<?php if ( ! empty( $metadata['meeting_title'] ) ) : ?>
<dt><?php _e( 'Meeting Title:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['meeting_title']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['meeting_organization'] ) ) : ?>
<dt><?php _e( 'Meeting Org.:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['meeting_organization']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['meeting_location'] ) ) : ?>
<dt><?php _e( 'Meeting Loc.:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['meeting_location']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['meeting_date'] ) ) : ?>
<dt><?php _e( 'Meeting Date:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['meeting_date']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php elseif ( 'Dissertation' == $metadata['genre'] || 'Technical report' == $metadata['genre'] || 'Thesis' == $metadata['genre'] ||
                 'White paper' == $metadata['genre'] ) : ?>
<dt><?php _e( 'Institution:', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $metadata['institution'] ) ) : ?>
<dd><span><?php echo $metadata['institution']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php endif; ?>
<dt><?php _e( 'Abstract:', 'humcore_domain' ); // Google Scholar wants Abstract. ?></dt>
<dd><?php echo $metadata['abstract_unchanged']; ?></dd>
<?php if ( 'yes' === $metadata['committee_deposit'] ) : // Do not show unless this is a committee deposit. ?>
<dt><?php _e( 'Deposit Type:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo 'Committee'; ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $project_directors_list ) ) : ?>
<dt><?php _e( 'Project Director(s):', 'humcore_domain' ); ?></dt>
<dd><?php echo implode( ', ', $project_directors_list ); // XSS OK. ?></dd>
<?php endif; ?>
<?php if ( ! empty( $authors_list ) ) : ?>
<dt><?php _e( 'Author(s):', 'humcore_domain' ); ?></dt>
<dd><?php echo implode( ', ', $authors_list ); // XSS OK. ?></dd>
<?php endif; ?>
<?php if ( ! empty( $editors_list ) ) : ?>
<dt><?php _e( 'Editor(s):', 'humcore_domain' ); ?></dt>
<dd><?php echo implode( ', ', $editors_list ); // XSS OK. ?></dd>
<?php endif; ?>
<?php if ( ! empty( $translators_list ) ) : ?>
<dt><?php _e( 'Translator(s):', 'humcore_domain' ); ?></dt>
<dd><?php echo implode( ', ', $translators_list ); // XSS OK. ?></dd>
<?php endif; ?>
<dt><?php _e( 'Subject(s):', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $subjects ) ) : ?>
<dd><?php echo $subject_list; // XSS OK. ?></dd>
<?php else : ?>
<dd>&nbsp;</dd>
<?php endif; ?>
<dt><?php _e( 'Group(s):', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $groups ) ) : ?>
<dd><?php echo $group_list; // XSS OK. ?></dd>
<?php else : ?>
<dd>&nbsp;</dd>
<?php endif; ?>
<dt><?php _e( 'Tag(s):', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $keywords ) ) : ?>
<dd><?php echo $keyword_list; // XSS OK. ?></dd>
<?php else : ?>
<dd>&nbsp;</dd>
<?php endif; ?>
<dt><?php _e( 'File Type:', 'humcore_domain' ); ?></dt>
<dd><?php echo esc_html( $metadata['type_of_resource'] ); ?></dd>
<dt><?php _e( 'Language:', 'humcore_domain' ); ?></dt>
<dd><?php echo esc_html( $metadata['language'] ); ?></dd>
<dt><?php _e( 'Notes:', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $metadata['notes_unchanged'] ) ) : ?>
<dd><?php echo $metadata['notes_unchanged']; ?></dd>
<?php else : ?>
<dd>( None )</dd>
<?php endif; ?>
<?php if ( 'journal-article' == $post_metadata['publication-type'] ) : ?>
<dt><?php _e( 'Pub. Type:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo 'Journal Article'; // XSS OK. ?></span></dd>
<dt><?php _e( 'Pub. DOI:', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $metadata['doi'] ) ) : ?>
<dd><span><?php echo $metadata['doi']; // XSS OK. ?></span></dd>
<?php else : ?>
<dd>&nbsp;</dd>
<?php endif; ?>
<dt><?php _e( 'Publisher:', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $metadata['publisher'] ) ) : ?>
<dd><span><?php echo $metadata['publisher']; // XSS OK. ?></span></dd>
<?php else : ?>
<dd>&nbsp;</dd>
<?php endif; ?>
<dt><?php _e( 'Pub. Date:', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $metadata['date'] ) ) : ?>
<dd><span><?php echo $metadata['date']; // XSS OK. ?></span></dd>
<?php else : ?>
<dd>&nbsp;</dd>
<?php endif; ?>
<dt><?php _e( 'Journal:', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $metadata['book_journal_title'] ) ) : ?>
<dd><span><?php echo $metadata['book_journal_title']; // XSS OK. ?></span></dd>
<?php else : ?>
<dd>&nbsp;</dd>
<?php endif; ?>
<dt><?php _e( 'Volume:', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $metadata['volume'] ) ) : ?>
<dd><span><?php echo $metadata['volume']; // XSS OK. ?></span></dd>
<?php else : ?>
<dd>&nbsp;</dd>
<?php endif; ?>
<dt><?php _e( 'Issue:', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $metadata['issue'] ) ) : ?>
<dd><span><?php echo $metadata['issue']; // XSS OK. ?></span></dd>
<?php else : ?>
<dd>&nbsp;</dd>
<?php endif; ?>
<dt><?php _e( 'Start Page:', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $metadata['start_page'] ) ) : ?>
<dd><span><?php echo $metadata['start_page']; // XSS OK. ?></span></dd>
<?php else : ?>
<dd>&nbsp;</dd>
<?php endif; ?>
<dt><?php _e( 'End Page:', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $metadata['end_page'] ) ) : ?>
<dd><span><?php echo $metadata['end_page']; // XSS OK. ?></span></dd>
<?php else : ?>
<dd>&nbsp;</dd>
<?php endif; ?>
<dt><?php _e( 'ISSN:', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $metadata['issn'] ) ) : ?>
<dd><span><?php echo $metadata['issn']; // XSS OK. ?></span></dd>
<?php else : ?>
<dd>&nbsp;</dd>
<?php endif; ?>
<?php elseif ( 'book-chapter' == $post_metadata['publication-type'] ) : ?>
<dt><?php _e( 'Pub. Type:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo 'Book chapter'; // XSS OK. ?></span></dd>
<dt><?php _e( 'Pub. DOI:', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $metadata['doi'] ) ) : ?>
<dd><span><?php echo $metadata['doi']; // XSS OK. ?></span></dd>
<?php else : ?>
<dd>&nbsp;</dd>
<?php endif; ?>
<dt><?php _e( 'Publisher:', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $metadata['publisher'] ) ) : ?>
<dd><span><?php echo $metadata['publisher']; // XSS OK. ?></span></dd>
<?php else : ?>
<dd>&nbsp;</dd>
<?php endif; ?>
<dt><?php _e( 'Pub. Date:', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $metadata['date'] ) ) : ?>
<dd><span><?php echo $metadata['date']; // XSS OK. ?></span></dd>
<?php else : ?>
<dd>&nbsp;</dd>
<?php endif; ?>
<dt><?php _e( 'Author/Editor:', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $metadata['book_author'] ) ) : ?>
<dd><span><?php echo $metadata['book_author']; // XSS OK. ?></span></dd>
<?php else : ?>
<dd>&nbsp;</dd>
<?php endif; ?>
<dt><?php _e( 'Book Title:', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $metadata['book_journal_title'] ) ) : ?>
<dd><span><?php echo $metadata['book_journal_title']; // XSS OK. ?></span></dd>
<?php else : ?>
<dd>&nbsp;</dd>
<?php endif; ?>
<dt><?php _e( 'Chapter:', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $metadata['chapter'] ) ) : ?>
<dd><span><?php echo $metadata['chapter']; // XSS OK. ?></span></dd>
<?php else : ?>
<dd>&nbsp;</dd>
<?php endif; ?>
<dt><?php _e( 'Start Page:', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $metadata['start_page'] ) ) : ?>
<dd><span><?php echo $metadata['start_page']; // XSS OK. ?></span></dd>
<?php else : ?>
<dd>&nbsp;</dd>
<?php endif; ?>
<dt><?php _e( 'End Page:', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $metadata['end_page'] ) ) : ?>
<dd><span><?php echo $metadata['end_page']; // XSS OK. ?></span></dd>
<?php else : ?>
<dd>&nbsp;</dd>
<?php endif; ?>
<dt><?php _e( 'ISBN:', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $metadata['isbn'] ) ) : ?>
<dd><span><?php echo $metadata['isbn']; // XSS OK. ?></span></dd>
<?php else : ?>
<dd>&nbsp;</dd>
<?php endif; ?>
<?php elseif ( 'proceedings-article' == $post_metadata['publication-type'] ) : ?>
<dt><?php _e( 'Pub. Type:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo 'Proceedings article'; // XSS OK. ?></span></dd>
<dt><?php _e( 'Pub. DOI:', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $metadata['doi'] ) ) : ?>
<dd><span><?php echo $metadata['doi']; // XSS OK. ?></span></dd>
<?php else : ?>
<dd>&nbsp;</dd>
<?php endif; ?>
<dt><?php _e( 'Publisher:', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $metadata['publisher'] ) ) : ?>
<dd><span><?php echo $metadata['publisher']; // XSS OK. ?></span></dd>
<?php else : ?>
<dd>&nbsp;</dd>
<?php endif; ?>
<dt><?php _e( 'Pub. Date:', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $metadata['date'] ) ) : ?>
<dd><span><?php echo $metadata['date']; // XSS OK. ?></span></dd>
<?php else : ?>
<dd>&nbsp;</dd>
<?php endif; ?>
<dt><?php _e( 'Proceeding:', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $metadata['book_journal_title'] ) ) : ?>
<dd><span><?php echo $metadata['book_journal_title']; // XSS OK. ?></span></dd>
<?php else : ?>
<dd>&nbsp;</dd>
<?php endif; ?>
<dt><?php _e( 'Start Page:', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $metadata['start_page'] ) ) : ?>
<dd><span><?php echo $metadata['start_page']; // XSS OK. ?></span></dd>
<?php else : ?>
<dd>&nbsp;</dd>
<?php endif; ?>
<dt><?php _e( 'End Page:', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $metadata['end_page'] ) ) : ?>
<dd><span><?php echo $metadata['end_page']; // XSS OK. ?></span></dd>
<?php else : ?>
<dd>&nbsp;</dd>
<?php endif; ?>
<?php elseif ( empty( $post_metadata['publication-type'] ) || 'none' == $post_metadata['publication-type'] ) : ?>
<dt><?php _e( 'Pub. Type:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo 'None'; // XSS OK. ?></span></dd>
<dt><?php _e( 'Creation Date:', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $metadata['date'] ) ) : ?>
<dd><?php echo esc_html( $metadata['date'] ); ?></dd>
<?php else : ?>
<dd>( None entered )</dd>
<?php endif; ?>
<?php endif; ?>
<?php if ( ! empty( $post_metadata['type_of_license'] ) ) : ?>
<dt><?php _e( 'License:', 'humcore_domain' ); ?></dt>
<dd><?php echo humcore_linkify_license( $post_metadata['type_of_license'] ); ?></dd>
<?php endif; ?>
<?php if ( 'draft' === $post_data->post_status ) : ?>
<dt><?php _e( 'Status:', 'humcore_domain' ); ?></dt>
<dd><?php echo '<strong>Provisional</strong>'; ?></dd>
<?php elseif ( 'pending' === $post_data->post_status ) : ?>
<dt><?php _e( 'Status:', 'humcore_domain' ); ?></dt>
<dd><?php echo 'Pending Review'; ?></dd>
<?php elseif ( 'publish' === $post_data->post_status ) : ?>
<dt><?php _e( 'Status:', 'humcore_domain' ); ?></dt>
<dd><?php echo 'Published'; ?></dd>
<?php endif; ?>
<?php if ( ! empty( $update_time ) ) : ?>
<dt><?php _e( 'Last Updated:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $update_time . ' ago'; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $post_metadata['embargoed'] ) ) : ?>
<dt><?php _e( 'Embargoed?:', 'humcore_domain' ); ?></dt>
<dd><?php echo $post_metadata['embargoed']; ?></dd>
<?php endif; ?>
<?php if ( ! empty( $post_metadata['embargo_end_date'] ) ) : ?>
<dt><?php _e( 'Embargo End Date:', 'humcore_domain' ); ?></dt>
<dd><?php echo $post_metadata['embargo_end_date']; ?></dd>
<?php endif; ?>
<?php if ( ! empty( $thumb_url ) ) : ?>
<dt><?php _e( 'Preview:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $thumb_url;// XSS OK. ?></span></dd>
<?php endif; ?>
<dt><?php _e( 'File Name:', 'humcore_domain' ); ?></dt>
<dd><?php echo esc_html( $file_metadata['files'][0]['filename'] ); ?></dd>
<dt><?php _e( 'File Size:', 'humcore_domain' ); ?></dt>
<dd><?php echo number_format( $file_metadata['files'][0]['filesize'] ), " bytes"; ?></dd>
</dl>
</div>
<br style='clear:both'>
<a class="bp-deposits-view button white" title="View" href="<?php echo esc_url( $item_url ); ?>"><?php _e( 'View your Deposit', 'humcore_domain' ); ?></a>
<?php

}

/**
 * Output the search sidebar facet list content.
 */
function humcore_search_sidebar_content() {

	$extended_query_string = humcore_get_search_request_querystring();
	$facet_display_counts = humcore_get_facet_counts();
	$facet_display_titles = humcore_get_facet_titles();
	$query_args = wp_parse_args( $extended_query_string ); ?>
	<ul class="facet-set"><?php
	foreach ( $facet_display_counts as $facet_key => $facet_values ) {
		$facet_list_count = 0;
		if ( ! empty( $facet_display_titles[ $facet_key ] ) ) : ?>
		<li class="facet-set-item"><h5><?php echo esc_html( trim( $facet_display_titles[ $facet_key ] ) ); ?></h5>
			<ul id="<?php echo sanitize_title_with_dashes( trim( $facet_key ) ); ?>-list" class="facet-list"><?php
			$sorted_counts = $facet_values['counts'];
			if ( "pub_date_facet" === $facet_key ) {
				arsort( $sorted_counts );
			}
			foreach ( $sorted_counts as $facet_value_counts ) {
				if ( ! empty( $facet_value_counts[0] ) ) {
					$facet_list_item_selected = false;
					if ( ! empty( $query_args['facets'][ $facet_key ] ) ) {
						if ( in_array( $facet_value_counts[0], $query_args['facets'][ $facet_key ] ) ) {
							$facet_list_item_selected = true;
						}
					}
					$display_count = sprintf( '<span class="count facet-list-item-count"%1$s>%2$s</span>',
						( $facet_list_item_selected ) ? ' style="display: none;"' : '',
						$facet_value_counts[1]
					);
					$display_selected = sprintf( '<span class="iconify facet-list-item-control%1$s"%2$s>%3$s</span>',
						( $facet_list_item_selected ) ? ' selected' : '',
						( $facet_list_item_selected ) ? '' : ' style="display: none !important;"',
						'X'
					);
					echo sprintf( '<li class="facet-list-item"%1$s><a class="facet-search-link" rel="nofollow" href="/deposits/?facets[%2$s][]=%3$s">%4$s %5$s%6$s</a></li>',
						( $facet_list_count < 2 || $facet_list_item_selected ) ? '' : ' style="display: none;"',
						trim( $facet_key ),
						urlencode( trim( $facet_value_counts[0] ) ),
						trim( $facet_value_counts[0] ),
						$display_count,
						$display_selected
					); // XSS OK.
					$facet_list_count++;
				}
			}
			if ( 2 < $facet_list_count ) {
				echo '<div class="facet-display-button"><span class="show-more button white right">' . esc_attr__( 'more>>', 'humcore_domain' ) . '</span></div>';
			} ?>
			</ul>
		</li><?php
		endif;
	} ?>
	</ul><?php

}

/**
 * Output the search sidebar facet list content.
 */
function humcore_directory_sidebar_content() {

	$extended_query_string = humcore_get_search_request_querystring();
	humcore_has_deposits( $extended_query_string );
	$facet_display_counts = humcore_get_facet_counts();
	$facet_display_titles = humcore_get_facet_titles();
	$query_args = wp_parse_args( $extended_query_string ); ?>
	<ul class="facet-set"><?php
	foreach ( $facet_display_counts as $facet_key => $facet_values ) {
		if ( ! in_array( $facet_key, array( 'genre_facet', 'subject_facet', 'pub_date_facet' ) ) ) { continue; }
		$facet_list_count = 0; ?>
		<li class="facet-set-item"><h5>Browse by <?php echo esc_html( trim( $facet_display_titles[ $facet_key ] ) ); ?></h5>
		<ul id="<?php echo sanitize_title_with_dashes( trim( $facet_key ) ); ?>-list" class="facet-list"><?php
		$sorted_counts = $facet_values['counts'];
		if ( "pub_date_facet" === $facet_key ) {
			arsort( $sorted_counts );
		}
		foreach ( $sorted_counts as $facet_value_counts ) {
			if ( ! empty( $facet_value_counts[0] ) ) {
				$facet_list_item_selected = false;
				if ( ! empty( $query_args['facets'][ $facet_key ] ) ) {
					if ( in_array( $facet_value_counts[0], $query_args['facets'][ $facet_key ] ) ) {
						$facet_list_item_selected = true;
					}
				}
				$display_count = sprintf( '<span class="count facet-list-item-count"%1$s>%2$s</span>',
					( $facet_list_item_selected ) ? ' style="display: none;"' : '',
					$facet_value_counts[1]
				);
				echo sprintf( '<li class="facet-list-item"%1$s><a class="facet-search-link" rel="nofollow" href="/deposits/?facets[%2$s][]=%3$s">%4$s %5$s</a></li>',
					( $facet_list_count < 4 || $facet_list_item_selected ) ? '' : ' style="display: none;"',
					trim( $facet_key ),
					urlencode( trim( $facet_value_counts[0] ) ),
					trim( $facet_value_counts[0] ),
					$display_count
				); // XSS OK.
				$facet_list_count++;
			}
		}
		if ( 4 < $facet_list_count ) {
			echo '<div class="facet-display-button"><span class="show-more button white right">' . esc_attr__( 'more>>', 'humcore_domain' ) . '</span></div>';
		} ?>
			</ul>
		</li>
	<?php }?>
	</ul>
<?php

}

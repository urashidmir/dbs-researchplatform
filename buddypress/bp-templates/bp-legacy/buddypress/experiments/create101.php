
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>


<!-- Optionally use Animate.css -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.0.0/animate.min.css">
<link rel="stylesheet" href="http://localhost/liquidslider/css/liquid-slider.css">

<!--link rel="stylesheet" href="http://localhost/wordpress/liquidslider/examples/assets/prism.css"-->
<!--link rel="stylesheet" href="http://localhost/wordpress/liquidslider/examples/assets/styles.css"-->

<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>

<script language="javascript" type="text/javascript">

var txtbox = '<tr><td><select><option value="volvo">Volvo</option><option value="saab">Saab</option><option value="mercedes">Mercedes</option><option value="audi">Audi</option></select></td></td>';

var variableAdded = false;
var variableCount = 0;

function addVariable() {
    var button = jq(this);
    //document.frm.appendChild(txtbox);
    //document.getElementById("add-variable-submit").value= "Hide Filter";
    
    
    if(variableAdded==false){
        variableAdded = true;
    }
    
    if(variableAdded)
        variableCount++;
    
    if(variableCount>0)
        document.getElementById("remove-variable-submit").style.visibility="visible";
    
    
    var varLabel = document.createElement("label");
    //var labelId = "label" + variableCount.toString();
    var labelValue = "Measurement " + (variableCount+2).toString()+"(optional)";
    var labelId = "label" + variableCount.toString();
    varLabel.setAttribute("for", "variable[]");
    varLabel.setAttribute("id", labelId);
    varLabel.innerHTML = labelValue;
    
    
    
    var textBox = document.createElement("input");
    //var textBoxId = "variable" + document.getElementsByTagName('input').length.toString();
    var textBoxId = "variable" + variableCount.toString();
    //Assign different attributes to the element.
    textBox.setAttribute("type", "text");
    textBox.setAttribute("name", "name[]");
    textBox.setAttribute("id", textBoxId);
    //textBox.setAttribute("value", textBoxId);
    
    
    var selectList = document.createElement("select");
    selectListBoxId = "type" + variableCount.toString();
    selectList.setAttribute("name", "type[]");
    selectList.setAttribute("id", selectListBoxId);
    
    
    var option1 = document.createElement("option");
    option1.text = "Score";
    option1.value = "score";
    
    var option2 = document.createElement("option");
    option2.text = "Binary";
    option2.value = "sinary";
    
    var option3 = document.createElement("option");
    option3.text = "Count";
    option3.value = "count";
    
    var option4 = document.createElement("option");
    option4.text = "Time";
    option4.value = "time";
    
    selectList.add(option1,selectList[0]);
    selectList.add(option2,selectList[1]);
    selectList.add(option3,selectList[2]);
    selectList.add(option4,selectList[3]);
    
    
    
    var selects=document.getElementsByTagName("select");
    var length = selects.length;
    
    //var parentGuest = document.getElementById("typeId[]");
    var parentGuest = selects[length-1];
    parentGuest.parentNode.insertBefore(selectList, parentGuest.nextSibling);
    parentGuest.parentNode.insertBefore(textBox, parentGuest.nextSibling);
    parentGuest.parentNode.insertBefore(varLabel, parentGuest.nextSibling);
    
    /*
     document.getElementById("add-variable-submit").parentNode.appendChild(textBox);
     document.getElementById("add-variable-submit").parentNode.appendChild(selectList);
     */
    
}

function removeVariable() {
    
    var length = document.getElementsByTagName('input').length;
    //length = length - 1;
    //if(length >2){
    
    var labelId = "label"+variableCount.toString();
    var varLabel = document.getElementById(labelId);
    varLabel.parentNode.removeChild(varLabel);
    
    var textBoxId = "variable"+variableCount.toString();
    var textBox = document.getElementById(textBoxId);
    textBox.parentNode.removeChild(textBox);
    
    
    var selectListId = "type"+variableCount.toString();
    selectList = document.getElementById(selectListId);
    selectList.parentNode.removeChild(selectList);
    
    
    variableCount =  variableCount-1;
    if(variableCount==0)
    {
        variableAdded = false;
        document.getElementById("remove-variable-submit").style.visibility="hidden";
    }
    
    
    
    //var form = document.getElementById("create-experiment-form");
    //form.removeChild(element);
    
}


</script>


<?php do_action( 'bp_before_create_experiment_page' ); ?>

<div id="buddypress">

<?php do_action( 'bp_before_create_experiment_content_template' ); ?>

<form action="<?php bp_experiment_creation_single_form_action(); ?>" method="post" id="create-experiment-form" class="standard-form" enctype="multipart/form-data">

<?php do_action( 'bp_before_create_experiment' ); ?>

<div class="item-list-tabs no-ajax" id="experiment-create-tabs" role="navigation">
<ul>

<!--?php bp_experiment_creation_tabs(); ?-->

</ul>
</div>

<?php do_action( 'template_notices' ); ?>




<div id="main-slider" class="liquid-slider">

    <div id="experiment-details">
        <h2 class="title1">Details</h2>
        <div align="right">
            <input type="button" class="btn-hover" value="Next" name="btn-details-next" id="btn-details-next" onclick="detailsNext()">
    </div>
    <div>

            <label for="experiment-name"><?php _e( 'Experiment Title (required)', 'buddypress' ); ?></label>
            <input type="text" name="experiment-name" id="experiment-name" aria-required="true" value="<?php bp_new_experiment_name(); ?>" />
    </div>

    <div>
        <label for="experiment-desc"><?php _e( 'Experiment Description (required)', 'buddypress' ); ?></label>
        <textarea name="experiment-desc" id="experiment-desc" aria-required="true"><?php bp_new_experiment_description(); ?></textarea>
    </div>
    </div><!--Details-->

    <div id="experiment-invites">
            <h2 class="title1">Invites</h2>

        <div align="right">
            <input type="button" class="btn-hover" value="Previous" name="btn-invites-previous" id="btn-invites-previous" onclick="invitesPrevious()">
        </div>


    </div><!--Invites-->


<div class="item-body" id="experiment-create-body">

<?php /* Experiment creation step 1: Basic experiment details */ ?>
<?php if ( bp_is_experiment_creation_step( 'experiment-details' ) ) : ?>

<?php do_action( 'bp_before_experiment_details_creation_step' ); ?>

<div>
<label for="experiment-name"><?php _e( 'Experiment Name (required)', 'buddypress' ); ?></label>
<input type="text" name="experiment-name" id="experiment-name" aria-required="true" value="<?php bp_new_experiment_name(); ?>" />
</div>

<div>
<label for="experiment-desc"><?php _e( 'Experiment Description (required)', 'buddypress' ); ?></label>
<textarea name="experiment-desc" id="experiment-desc" aria-required="true"><?php bp_new_experiment_description(); ?></textarea>
</div>

<?php
    do_action( 'bp_after_experiment_details_creation_step' );
    do_action( 'experiments_custom_experiment_fields_editable' ); // @Deprecated
    
    wp_nonce_field( 'experiments_create_save_experiment-details' ); ?>

<?php endif; ?>




<?php /* experiment creation step 2: experiment variables */ ?>
<?php if ( bp_is_experiment_creation_step( 'experiment-variables' ) ) : ?>

<?php do_action( 'bp_before_experiment_variables_creation_step' ); ?>

<label for="experiment-variable1"><?php _e( 'Measurement 1 (required)', 'buddypress' ); ?></label>
<input type="text" name="name[]" id="name[]" aria-required="true" value="<?php bp_new_experiment_variable(); ?>" />

<select id="typeId[]" name="type[]">
<option value="score"><?php _e( 'Score', 'buddypress' ); ?></option>
<option value="binary"><?php _e( 'Binary', 'buddypress' ); ?></option>
<option value="count"><?php _e( 'Count', 'buddypress' ); ?></option>
<option value="count"><?php _e( 'Time', 'buddypress' ); ?></option>

<?php do_action( 'bp_experiment_variable_type_options' ); ?>
</select>

<label for="experiment-variable2"><?php _e( 'Measurement 2 (required)', 'buddypress' ); ?></label>
<input type="text" name="name[]" id="name[]" aria-required="true" value="<?php bp_new_experiment_variable(); ?>" />

<select id="typeId[]" name="type[]">
<option value="score"><?php _e( 'Score', 'buddypress' ); ?></option>
<option value="binary"><?php _e( 'Binary', 'buddypress' ); ?></option>
<option value="count"><?php _e( 'Count', 'buddypress' ); ?></option>
<option value="count"><?php _e( 'Time', 'buddypress' ); ?></option>

<?php do_action( 'bp_experiment_variable_type_options' ); ?>
</select>



<tr>
<td align="right" width="100%">
<input type="button" value="Add Variable" name="add-variable-submit" id="add-variable-submit" onclick="addVariable()">
<input type="button" value="Remove Variable" name="remove-variable-submit" id="remove-variable-submit" onclick="removeVariable()" style="visibility: hidden;">

</td>
</tr>

<?php
    do_action( 'bp_after_experiment_variables_creation_step' );
    do_action( 'experiments_custom_experiment_fields_editable' ); // @Deprecated
    
    wp_nonce_field( 'experiments_create_save_experiment-variables' ); ?>

<?php endif; ?>






<?php /* Experiment creation step 3: Experiment settings */ ?>
<?php if ( bp_is_experiment_creation_step( 'experiment-settings' ) ) : ?>

<?php do_action( 'bp_before_experiment_settings_creation_step' ); ?>

<h4><?php _e( 'Privacy Options', 'buddypress' ); ?></h4>

<div class="radio">
<label><input type="radio" name="experiment-status" value="public"<?php if ( 'public' == bp_get_new_experiment_status() || !bp_get_new_experiment_status() ) { ?> checked="checked"<?php } ?> />
<strong><?php _e( 'This is a public experiment', 'buddypress' ); ?></strong>
<ul>
<li><?php _e( 'Any site member can join this experiment.', 'buddypress' ); ?></li>
<li><?php _e( 'This experiment will be listed in the experiments directory and in search results.', 'buddypress' ); ?></li>
<li><?php _e( 'Experiment content and activity will be visible to any site member.', 'buddypress' ); ?></li>
</ul>
</label>

<label><input type="radio" name="experiment-status" value="private"<?php if ( 'private' == bp_get_new_experiment_status() ) { ?> checked="checked"<?php } ?> />
<strong><?php _e( 'This is a private experiment', 'buddypress' ); ?></strong>
<ul>
<li><?php _e( 'Only users who request membership and are accepted can join the experiment.', 'buddypress' ); ?></li>
<li><?php _e( 'This experiment will be listed in the experiments directory and in search results.', 'buddypress' ); ?></li>
<li><?php _e( 'Experiment content and activity will only be visible to members of the experiment.', 'buddypress' ); ?></li>
</ul>
</label>

<label><input type="radio" name="experiment-status" value="hidden"<?php if ( 'hidden' == bp_get_new_experiment_status() ) { ?> checked="checked"<?php } ?> />
<strong><?php _e('This is a hidden experiment', 'buddypress' ); ?></strong>
<ul>
<li><?php _e( 'Only users who are invited can join the experiment.', 'buddypress' ); ?></li>
<li><?php _e( 'This experiment will not be listed in the experiments directory or search results.', 'buddypress' ); ?></li>
<li><?php _e( 'Experiment content and activity will only be visible to members of the experiment.', 'buddypress' ); ?></li>
</ul>
</label>
</div>

<h4><?php _e( 'Experiment Invitations', 'buddypress' ); ?></h4>

<p><?php _e( 'Which members of this experiment are allowed to invite others?', 'buddypress' ); ?></p>

<div class="radio">
<label>
<input type="radio" name="experiment-invite-status" value="members"<?php bp_experiment_show_invite_status_setting( 'members' ); ?> />
<strong><?php _e( 'All experiment members', 'buddypress' ); ?></strong>
</label>

<label>
<input type="radio" name="experiment-invite-status" value="mods"<?php bp_experiment_show_invite_status_setting( 'mods' ); ?> />
<strong><?php _e( 'Experiment admins and mods only', 'buddypress' ); ?></strong>
</label>

<label>
<input type="radio" name="experiment-invite-status" value="admins"<?php bp_experiment_show_invite_status_setting( 'admins' ); ?> />
<strong><?php _e( 'Experiment admins only', 'buddypress' ); ?></strong>
</label>
</div>

<?php if ( bp_is_active( 'forums' ) ) : ?>

<h4><?php _e( 'Experiment Forums', 'buddypress' ); ?></h4>

<?php if ( bp_forums_is_installed_correctly() ) : ?>

<p><?php _e( 'Should this experiment have a forum?', 'buddypress' ); ?></p>

<div class="checkbox">
<label><input type="checkbox" name="experiment-show-forum" id="experiment-show-forum" value="1"<?php checked( bp_get_new_experiment_enable_forum(), true, true ); ?> /> <?php _e( 'Enable discussion forum', 'buddypress' ); ?></label>
</div>
<?php elseif ( is_super_admin() ) : ?>

<p><?php printf( __( '<strong>Attention Site Admin:</strong> Experiment forums require the <a href="%s">correct setup and configuration</a> of a bbPress installation.', 'buddypress' ), bp_core_do_network_admin() ? network_admin_url( 'settings.php?page=bb-forums-setup' ) :  admin_url( 'admin.php?page=bb-forums-setup' ) ); ?></p>

<?php endif; ?>

<?php endif; ?>

<?php do_action( 'bp_after_experiment_settings_creation_step' ); ?>

<?php wp_nonce_field( 'experiments_create_save_experiment-settings' ); ?>

<?php endif; ?>

<?php /* Experiment creation step 3: Avatar Uploads */ ?>
<?php if ( bp_is_experiment_creation_step( 'experiment-avatar' ) ) : ?>

<?php do_action( 'bp_before_experiment_avatar_creation_step' ); ?>

<?php if ( 'upload-image' == bp_get_avatar_admin_step() ) : ?>

<div class="left-menu">

<?php bp_new_experiment_avatar(); ?>

</div><!-- .left-menu -->

<div class="main-column">
<p><?php _e( "Upload an image to use as an avatar for this experiment. The image will be shown on the main experiment page, and in search results.", 'buddypress' ); ?></p>

<p>
<input type="file" name="file" id="file" />
<input type="submit" name="upload" id="upload" value="<?php esc_attr_e( 'Upload Image', 'buddypress' ); ?>" />
<input type="hidden" name="action" id="action" value="bp_avatar_upload" />
</p>

<p><?php _e( 'To skip the avatar upload process, hit the "Next Step" button.', 'buddypress' ); ?></p>
</div><!-- .main-column -->

<?php endif; ?>

<?php if ( 'crop-image' == bp_get_avatar_admin_step() ) : ?>

<h4><?php _e( 'Crop Experiment Avatar', 'buddypress' ); ?></h4>

<img src="<?php bp_avatar_to_crop(); ?>" id="avatar-to-crop" class="avatar" alt="<?php esc_attr_e( 'Avatar to crop', 'buddypress' ); ?>" />

<div id="avatar-crop-pane">
<img src="<?php bp_avatar_to_crop(); ?>" id="avatar-crop-preview" class="avatar" alt="<?php esc_attr_e( 'Avatar preview', 'buddypress' ); ?>" />
</div>

<input type="submit" name="avatar-crop-submit" id="avatar-crop-submit" value="<?php esc_attr_e( 'Crop Image', 'buddypress' ); ?>" />

<input type="hidden" name="image_src" id="image_src" value="<?php bp_avatar_to_crop_src(); ?>" />
<input type="hidden" name="upload" id="upload" />
<input type="hidden" id="x" name="x" />
<input type="hidden" id="y" name="y" />
<input type="hidden" id="w" name="w" />
<input type="hidden" id="h" name="h" />

<?php endif; ?>

<?php do_action( 'bp_after_experiment_avatar_creation_step' ); ?>

<?php wp_nonce_field( 'experiments_create_save_experiment-avatar' ); ?>

<?php endif; ?>

<?php /* Experiment creation step 4: Invite friends to experiment */ ?>
<?php if ( bp_is_experiment_creation_step( 'experiment-invites' ) ) : ?>

<?php do_action( 'bp_before_experiment_invites_creation_step' ); ?>

<?php if ( bp_is_active( 'friends' ) && bp_get_total_friend_count( bp_loggedin_user_id() ) ) : ?>

<div class="left-menu">

<div id="invite-list">
<ul>
<?php bp_new_experiment_invite_friend_list(); ?>
</ul>

<?php wp_nonce_field( 'experiments_invite_uninvite_user', '_wpnonce_invite_uninvite_user' ); ?>
</div>

</div><!-- .left-menu -->

<div class="main-column">

<div id="message" class="info">
<p><?php _e('Select people to invite from your friends list.', 'buddypress' ); ?></p>
</div>

<?php /* The ID 'friend-list' is important for AJAX support. */ ?>
<ul id="friend-list" class="item-list" role="main">

<?php if ( bp_experiment_has_invites() ) : ?>

<?php while ( bp_experiment_invites() ) : bp_experiment_the_invite(); ?>

<li id="<?php bp_experiment_invite_item_id(); ?>">

<?php bp_experiment_invite_user_avatar(); ?>

<h4><?php bp_experiment_invite_user_link(); ?></h4>
<span class="activity"><?php bp_experiment_invite_user_last_active(); ?></span>

<div class="action">
<a class="remove" href="<?php bp_experiment_invite_user_remove_invite_url(); ?>" id="<?php bp_experiment_invite_item_id(); ?>"><?php _e( 'Remove Invite', 'buddypress' ); ?></a>
</div>
</li>

<?php endwhile; ?>

<?php wp_nonce_field( 'experiments_send_invites', '_wpnonce_send_invites' ); ?>

<?php endif; ?>

</ul>

</div><!-- .main-column -->

<?php else : ?>

<div id="message" class="info">
<p><?php _e( 'Once you have built up friend connections you will be able to invite others to your experiment.', 'buddypress' ); ?></p>
</div>

<?php endif; ?>

<?php wp_nonce_field( 'experiments_create_save_experiment-invites' ); ?>

<?php do_action( 'bp_after_experiment_invites_creation_step' ); ?>

<?php endif; ?>

<?php do_action( 'experiments_custom_create_steps' ); // Allow plugins to add custom experiment creation steps ?>

<?php do_action( 'bp_before_experiment_creation_step_buttons' ); ?>

<?php if ( 'crop-image' != bp_get_avatar_admin_step() ) : ?>

<div class="submit" id="previous-next">


<?php /* Create Button */ ?>
<?php if ( bp_is_first_experiment_creation_step() ) : ?>

<input type="submit" value="<?php esc_attr_e( 'Create Experiment and Continue', 'buddypress' ); ?>" id="experiment-creation-create" name="save" />

<?php endif; ?>

</div>

<?php endif;?>

<?php do_action( 'bp_after_experiment_creation_step_buttons' ); ?>

<?php /* Don't leave out this hidden field */ ?>
<input type="hidden" name="experiment_id" id="experiment_id" value="<?php bp_new_experiment_id(); ?>" />

<?php do_action( 'bp_directory_experiments_content' ); ?>

</div><!-- .item-body -->

</div><!-- .main-slider -->


<?php do_action( 'bp_after_create_experiment' ); ?>

</form>

<?php do_action( 'bp_after_create_experiment_content_template' ); ?>

</div><!-- buddypress -->

<?php do_action( 'bp_after_create_experiment_page' ); ?>




<footer>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
<!--script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.3/jquery.easing.min.js"></script-->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.touchswipe/1.6.4/jquery.touchSwipe.min.js"></script>
<script src="http://localhost/liquidslider/js/jquery.liquid-slider.min.js"></script>
<script>
/**
 * If you need to access the internal property or methods, use this:
 * var api = $.data( $('#main-slider')[0], 'liquidSlider');
 */
/*
 $('#main-slider').liquidSlider({
 
 continuous:false,
 slideEaseFunction: "easeInOutCubic"
 });
 */

$('#main-slider').liquidSlider({
                               //firstPanelToLoad:1
                               
                               //dynamicTabs: false, //remove the tabs from the slider
                               //panelTitleSelector: "title", //use the h3 class=slide_title as the title of the slide
                               //crossLinks: false, //allow external anchors (not inside the slider) to push or pull to the relevant slide (anchors tags require: data-liquidslider-ref="SLIDER-ID")
                               //hashLinking: false, //allows for us to use hashes (#) as a link to push or pull slides
                               //hashCrossLinks: false, //allows us to use cross link's hashes as links to go to specific slides
                               //hashNames: true,
                               //hashTitleSelector: "h3.slide_title", //specifies the name of the hash which corresponds to the specific slide
                               
                               
                               
                               });

var api = $.data( $('#main-slider')[0], 'liquidSlider');

function detailsNext() {
    
    var name = document.getElementById("experiment-name").value;
    var desc = document.getElementById("experiment-desc").value;
    if(name!='' && desc!='')
    {
        api.setNextPanel(1);
    }
    
}


function variablesPrevious() {
    api.setNextPanel(0);
    
}

function variablesNext() {
    
    var names=document.getElementsByName("name[]");
    var length = names.length;
    var empty=false;
    
    //alert("hello"+length);
    for(var i=0; i<length; i++){
        if(names[i].value==''){
            empty=true;
            break;
        }
    }//end for
    
    if(!empty)
        api.setNextPanel(2);
    
}


function settingsPrevious() {
    api.setNextPanel(1);
    
}

function settingsNext() {
    api.setNextPanel(3);
    
}


function invitesPrevious() {
    api.setNextPanel(2);
    
}

/*
 function settingsNext() {
 api.setNextPanel(3);
 
 }
 */

/*
 $('.btn-hover').on('click', function() {
 
 document.getElementById("next-submit").value= "Hide Filter";
 var button = document.getElementById("next-submit");
 //api.setNextPanel(1);
 
 });
 */

/*
 $('.next-submit').on('click', function () {
 api.setNextPanel(1);
 });
 */

/*
 $('.btn-load').on('click', function () {
 api.setNextPanel(1);
 });
 */

</script>
</footer>





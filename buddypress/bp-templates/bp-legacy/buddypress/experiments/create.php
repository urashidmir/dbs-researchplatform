
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>

<!-- Optionally use Animate.css -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.0.0/animate.min.css">
<link rel="stylesheet" href="http://localhost/liquidslider/css/liquid-slider.css">

<!--link rel="stylesheet" href="http://localhost/wordpress/liquidslider/examples/assets/prism.css"-->
<!--link rel="stylesheet" href="http://localhost/wordpress/liquidslider/examples/assets/styles.css"-->

<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>

<script language="javascript" type="text/javascript">

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
    
    if(variableCount>0){
        //document.getElementById("remove-variable-submit").style.visibility="visible";
        
        var div = document.getElementById("experiment-variables");
        //$(div).find('input[name="remove-variable-submit"]').style.visibility="visible";
        
        //$('#experiment-variables input[name="remove-variable-submit"]').attr("style", "visibility: visible");
        $('[name="remove-variable-submit"]').attr("style", "visibility: visible");

    }
        
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
        //document.getElementById("remove-variable-submit").style.visibility="hidden";
        //$('#experiment-variables input[name="remove-variable-submit"]').attr("style", "visibility: hidden");
        $('[name="remove-variable-submit"]').attr("style", "visibility: hidden");
    }
    
    
    
    //var form = document.getElementById("create-experiment-form");
    //form.removeChild(element);
    
}

</script>


<?php do_action( 'bp_before_create_experiment_page' ); ?>

<div id="buddypress">

<?php do_action( 'bp_before_create_experiment_content_template' ); ?>

<form action="<?php bp_experiment_creation_single_form_action(); ?>" method="post" id="create-experiment-form" name="create-experiment-form" class="standard-form" enctype="multipart/form-data">

<?php do_action( 'bp_before_create_experiment' ); ?>

<div class="item-list-tabs no-ajax" id="experiment-create-tabs" role="navigation">
<ul>

<!--?php bp_experiment_creation_tabs(); ?-->

</ul>
</div>

<?php do_action( 'template_notices' ); ?>

<div class="item-body" id="experiment-create-body">

<?php /* Experiment creation step 1: Basic experiment details */ ?>
<!--?php if ( bp_is_experiment_creation_step( 'experiment-details' ) ) : ?-->

<div class="liquid-slider" id="main-slider">

<div id="experiment-details">
<h2 class="title1">Details</h2>

<?php do_action( 'bp_before_experiment_details_creation_step' ); ?>

<div align="right">

        <input type="button" tabindex="-1" class="btn-hover" value="Next" name="btn-details-next" id="btn-details-next" onclick="detailsNext()">

</div>
<div>
<label for="experiment-name"><?php _e( 'Experiment Name (required)', 'buddypress' ); ?></label>
<input type="text" tabindex="-1" name="experiment-name" id="experiment-name" aria-required="true" value="<?php bp_new_experiment_name(); ?>" />
</div>

<div>
<label for="experiment-desc"><?php _e( 'Experiment Description (required)', 'buddypress' ); ?></label>
<textarea name="experiment-desc" tabindex="-1" id="experiment-desc" aria-required="true"><?php bp_new_experiment_description(); ?></textarea>
</div>

<?php
    do_action( 'bp_after_experiment_details_creation_step' );
    do_action( 'experiments_custom_experiment_fields_editable' ); // @Deprecated
    
    wp_nonce_field( 'experiments_create_save_experiment-details' ); ?>

<!--?php endif; ?-->


</div><!--Details-->

<div id="experiment-variables">

<h2 class="title1">Variables</h2>

<div align="right">

    <input type="button" tabindex="-1" class="btn-hover" value="Previous" name="btn-variables-previous" id="btn-variables-next" onclick="variablesPrevious()">
    <input type="button" tabindex="-1" class="btn-hover" value="Next" name="btn-variables-next" id="btn-variables-next" onclick="variablesNext()">

</div>

<label for="experiment-variable1"><?php _e( 'Measurement 1 (required)', 'buddypress' ); ?></label>
<input type="text" tabindex="-1" name="name[]" id="name[]" aria-required="true" value="<?php bp_new_experiment_variable(); ?>" />

<select id="typeId[]" name="type[]" tabindex="-1">
<option value="score"><?php _e( 'Score', 'buddypress' ); ?></option>
<option value="binary"><?php _e( 'Binary', 'buddypress' ); ?></option>
<option value="count"><?php _e( 'Count', 'buddypress' ); ?></option>
<option value="count"><?php _e( 'Time', 'buddypress' ); ?></option>

<?php do_action( 'bp_experiment_variable_type_options' ); ?>
</select>




<label for="experiment-variable2"><?php _e( 'Measurement 2 (required)', 'buddypress' ); ?></label>
<input type="text" tabindex="-1" name="name[]" id="name[]" aria-required="true" value="<?php bp_new_experiment_variable(); ?>" />

<select id="typeId[]" name="type[]" tabindex="-1">
<option value="score"><?php _e( 'Score', 'buddypress' ); ?></option>
<option value="binary"><?php _e( 'Binary', 'buddypress' ); ?></option>
<option value="count"><?php _e( 'Count', 'buddypress' ); ?></option>
<option value="count"><?php _e( 'Time', 'buddypress' ); ?></option>

<?php do_action( 'bp_experiment_variable_type_options' ); ?>
</select>


<tr>
    <td align="right" width="100%">
        <input type="button" value="Add Variable" tabindex="-1" name="add-variable-submit" id="add-variable-submit" onclick="addVariable()">
        <input type="button" value="Remove Variable" tabindex="-1" name="remove-variable-submit" id="remove-variable-submit" onclick="removeVariable()" style="visibility: hidden;">

    </td>
</tr>

</div>!--Variables-->

<div id="experiment-settings">

    <h2 class="title1">Privacy Options</h2>

    <div align="right">

        <input type="button" tabindex="-1" class="btn-hover" value="Previous" name="btn-settings-previous" id="btn-settings-next" onclick="settingsPrevious()">
        <input type="button" tabindex="-1" class="btn-hover" value="Next" name="btn-settings-next" id="btn-settings-next" onclick="settingsNext()">

    </div>


<div class="radio">
<label><input type="radio" tabindex="-1" name="experiment-status" value="public"<?php if ( 'public' == bp_get_new_experiment_status() || !bp_get_new_experiment_status() ) { ?> checked="checked"<?php } ?> />
<strong><?php _e( 'This is a public experiment', 'buddypress' ); ?></strong>
<ul>
<li><?php _e( 'Any site member can join this experiment.', 'buddypress' ); ?></li>
<li><?php _e( 'This experiment will be listed in the experiments directory and in search results.', 'buddypress' ); ?></li>
<li><?php _e( 'Experiment content and activity will be visible to any site member.', 'buddypress' ); ?></li>
</ul>
</label>

<label><input type="radio" tabindex="-1"  name="experiment-status" value="private"<?php if ( 'private' == bp_get_new_experiment_status() ) { ?> checked="checked"<?php } ?> />
<strong><?php _e( 'This is a private experiment', 'buddypress' ); ?></strong>
<ul>
<li><?php _e( 'Only users who request membership and are accepted can join the experiment.', 'buddypress' ); ?></li>
<li><?php _e( 'This experiment will be listed in the experiments directory and in search results.', 'buddypress' ); ?></li>
<li><?php _e( 'Experiment content and activity will only be visible to members of the experiment.', 'buddypress' ); ?></li>
</ul>
</label>

<label><input type="radio" tabindex="-1"  name="experiment-status" value="hidden"<?php if ( 'hidden' == bp_get_new_experiment_status() ) { ?> checked="checked"<?php } ?> />
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
<input type="radio" tabindex="-1"  name="experiment-invite-status" value="members"<?php bp_experiment_show_invite_status_setting( 'members' ); ?> />
<strong><?php _e( 'All experiment members', 'buddypress' ); ?></strong>
</label>

<label>
<input type="radio" tabindex="-1"  name="experiment-invite-status" value="mods"<?php bp_experiment_show_invite_status_setting( 'mods' ); ?> />
<strong><?php _e( 'Experiment admins and mods only', 'buddypress' ); ?></strong>
</label>

<label>
<input type="radio" tabindex="-1"  name="experiment-invite-status" value="admins"<?php bp_experiment_show_invite_status_setting( 'admins' ); ?> />
<strong><?php _e( 'Experiment admins only', 'buddypress' ); ?></strong>
</label>
</div>


</div>!--Settings-->


<div id="experiment-invites">

<h2 class="title1">Invites</h2>

<div align="right">

<input type="button" tabindex="-1"  class="btn-hover" value="Previous" name="btn-invites-previous" id="btn-invites-next" onclick="invitesPrevious()">

<input type="submit" tabindex="-1"  value="<?php esc_attr_e( 'Finish', 'buddypress' ); ?>" id="experiment-creation-finish" name="save"/>

</div>


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

<li id="<?php bp_experiment_invite_item_id(); ?>" >

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




</div><!--Invites-->


</div><!--main-slider-->


<?php do_action( 'experiments_custom_create_steps' ); // Allow plugins to add custom experiment creation steps ?>

<?php do_action( 'bp_before_experiment_creation_step_buttons' ); ?>

<?php do_action( 'bp_after_experiment_creation_step_buttons' ); ?>

<?php /* Don't leave out this hidden field */ ?>
<input type="hidden" name="experiment_id" id="experiment_id" value="<?php bp_new_experiment_id(); ?>" />
<input type="hidden" name="experiment-name" id="experiment-name"  />
<input type="hidden" name="experiment-desc" id="experiment-desc" />


<?php do_action( 'bp_directory_experiments_content' ); ?>

</div><!-- .item-body -->

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


$(document).ready(function(){

                  $('[name="save"]').click(function (){
                                                         
                         var name = document.getElementById("experiment-name").value;
                         var desc = document.getElementById("experiment-desc").value;
                         
                         $('input[name="experiment-name"]').attr('value',name);
                         $('input[name="experiment-desc"]').attr('value',desc);
                         
                         var bla = $('#experiment-name').val();
                         //alert("hello");
                         
                         var variableNames=document.getElementsByName("name[]");
                         var variableTypes=document.getElementsByName("type[]");
                         var length = variableNames.length;
                         var empty=false;
                         
                         //alert(length);
                         //alert(variableTypes.length);
                         
                         
                         for(var i=0; i<length; i++){
                         
                         //alert(variableNames[i].value);
                         
                                           $('<input>').attr({
                                                             type: 'hidden',
                                                             id: 'name[]',
                                                             name: 'name[]',
                                                             value: variableNames[i].value
                                                             }).appendTo('create-experiment-form');
                         
                                           $('<input>').attr({
                                                             type: 'hidden',
                                                             id: 'type[]',
                                                             name: 'type[]',
                                                             value: variableTypes[i].value
                                                             }).appendTo('create-experiment-form')
                         
                         
                         
                         }//end for
                         
                         var experimentStatus=document.getElementsByName("experiment-status");
                         $('<input>').attr({
                                           type: 'hidden',
                                           id: 'experiment-status',
                                           name: 'experiment-status',
                                           value: experimentStatus
                                           }).appendTo('create-experiment-form');
                         
                         
                         var experimentInviteStatus=document.getElementsByName("experiment-invite-status");
                         $('<input>').attr({
                                           type: 'hidden',
                                           id: 'experiment-invite-status',
                                           name: 'experiment-invite-status',
                                           value: experimentInviteStatus
                                           }).appendTo('create-experiment-form');
                         
                         
                         var friends=document.getElementsByName("friends[]");
                         //alert(friends.length);
                         
                         for(var i=0; i<lfriends.length; i++){
                         $('<input>').attr({
                                           type: 'hidden',
                                           //id: 'friends[]',
                                           name: 'friends[]',
                                           value: friends[i].value
                                           }).appendTo('create-experiment-form');
                         }
                         
                         
                         $('[name="create-experiment-form"]').submit();
                         
                         
                         });//end click.function()
                  
                  
                  
                  }); //end ready.function()

$('#main-slider').liquidSlider({
                               //firstPanelToLoad:0
                               
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
    
    //alert("details");
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

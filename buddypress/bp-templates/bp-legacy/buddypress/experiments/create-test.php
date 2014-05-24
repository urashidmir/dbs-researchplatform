
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>


<!-- Optionally use Animate.css -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.0.0/animate.min.css">
<link rel="stylesheet" href="http://localhost/wordpress/liquidslider/css/liquid-slider.css">

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




<div id="main-slider" class="liquid-slider">

<div id="experiment-details">
<h2 class="title">Details</h2>
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


<div id="experiment-variables">
<h2 class="title">Variables</h2>
    <div align="right">
        <input type="button" class="btn-hover" value="Next" name="btn-variables-previous" id="btn-variables-next" onclick="variablesNext()">
    </div>
    <div>
        <label for="experiment-name"><?php _e( 'Experiment Title (required)', 'buddypress' ); ?></label>
        <input type="text" name="experiment-name" id="experiment-name" aria-required="true" value="<?php bp_new_experiment_name(); ?>" />
    </div>

    <div>
        <label for="experiment-desc"><?php _e( 'Experiment Description (required)', 'buddypress' ); ?></label>
        <textarea name="experiment-desc" id="experiment-desc" aria-required="true"><?php bp_new_experiment_description(); ?></textarea>
    </div>
</div><!--Variables-->

</div> <!--main-slider-->




<footer>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
<!--script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.3/jquery.easing.min.js"></script-->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.touchswipe/1.6.4/jquery.touchSwipe.min.js"></script>
<script src="http://localhost/wordpress/liquidslider/js/jquery.liquid-slider.min.js"></script>
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
                               firstPanelToLoad:0
                               
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


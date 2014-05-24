
<?php if ( is_user_logged_in() && bp_experiment_is_member() ) : ?>



<?php

    
if(isset($_POST['report']))
{
    //echo "Success";
    global $wpdb, $bp;
    
    
    //echo $_POST['variable_id'][0];
    //echo $_POST['variable'][0];
    
    //echo $_POST['variable_id'][1];
    //echo $_POST['variable'][1];
    

    $date_modified = new DateTime();
    $date_modified = (string) $date_modified->format('Y-m-d H:i:s');
    
    
    
    $variable1_id = $_POST['variable_id'][0];
    $variable1_value = $_POST['variable'][0];
    
    $variable2_id = $_POST['variable_id'][1];
    $variable2_value = $_POST['variable'][1];
    
    $variable_ids = $_POST['variable_id'];
    $variable_values = $_POST['variable'];
    
    
    for($x = 0; $x < count($variable_ids); $x++ )
    {
        //foreach ($name as $key => $val)
        //echo ($names[$x]);
        
        $sql = $wpdb->prepare(
                              "INSERT INTO wp_bp_experiments_report (
                              experiment_id,
                              user_id,
                              variable_id,
                              variable_value,
                              date_modified
                              ) VALUES (
                                        %d, %d, %d, %s, %s
                                        )",
        bp_get_current_experiment_id(),
        bp_loggedin_user_id(),
        $variable_ids[$x],
        $variable_values[$x],
        $date_modified
        );
        
        
        if ( !$wpdb->query( $sql ) )
            echo "Failure";

    }

    /*

    $sql = $wpdb->prepare(
                          "INSERT INTO wp_bp_experiments_report (
                          experiment_id,
                          user_id,
                          variable_id,
                          variable_value,
                          date_modified
                          ) VALUES (
                                    %d, %d, %d, %s, %s
                                    )",
    bp_get_current_experiment_id(),
    bp_loggedin_user_id(),
    $variable1_id,
    $variable1_value,
    $date_modified
    );
    
    
    if ( !$wpdb->query( $sql ) )
        echo "Failure";
    //else
    //    echo "Success";
    
    
    
    $sql = $wpdb->prepare(
                          "INSERT INTO wp_bp_experiments_report (
                          experiment_id,
                          user_id,
                          variable_id,
                          variable_value,
                          date_modified
                          ) VALUES (
                                    %d, %d, %d, %s, %s
                                    )",
    bp_get_current_experiment_id(),
    bp_loggedin_user_id(),
    $variable2_id,
    $variable2_value,
    $date_modified
    );
    
    if ( !$wpdb->query( $sql ) )
        echo "Failure";
    //else
    //    echo "Success";
     */
}
?>


<form action="" method="post" id="report-experiment-form" name="report-experiment-form"  role="complementary">


<?php
    
    $experimentid = bp_get_current_experiment_id();
    
    //echo $experimentid;
    
    // Create a connection
    $connection = mysql_connect("localhost", "root", "") or die(mysql_error());
    //$connection = mysql_connect("localhost", "urashid", "password") or die(mysql_error());
    
    //Select database
    mysql_select_db("wordpress", $connection) or die(mysql_error());

	$result=mysql_query("select * from wp_bp_experiments_variables where experiment_id=$experimentid");
    
	$cols=1;		// Here we define the number of columns
    

    global $variable_name1;
    global $variable_name2;
    
    //echo "experimentid="+$experimentid;
?>
    

<table>
<?php
    
	do{
        
?>
		<tr>
<?php
        //if($result)
        {
		for($i=1;$i<=$cols;$i++){	// All the rows will have $cols columns even if
            // the records are less than $cols

                $row=mysql_fetch_array($result);
             if($row){
                if(i==1)
                    $variable_name1 = $row['name'];
                if(i==2)
                    $variable_name2 = $row['name'];
                //echo $row['id'];
                
				//$img = $row['image_path'];
?>

<td>
<table>
<tr valign="top">
<td width="50%">

<label for="experiment-variable1"><b><?php _e( $row['name'], 'buddypress' ); ?></label>
</td>

<td>


<?php

if($row['type'] == 'count')
{

?>
    <input type="text" name="variable[]" id="$row['id']" aria-required="true"  />

<?php
    
}

if($row['type'] == 'score')
{

?>
    
    <select id="$row['id']" name="variable[]">
    <option value="1">1</option>
    <option value="2">2</option>
    <option value="3">3</option>
    <option value="4">4</option>
    <option value="5">5</option>
    <option value="6">6</option>
    <option value="7">7</option>
    <option value="8">8</option>
    <option value="9">9</option>
    <option value="10">10</option>
    </select>


<?php
    
}

if($row['type'] == 'binary')
{
    
?>
    <select id="$row['id']" name="variable[]">
    <option value="Yes">Yes</option>
    <option value="No">No</option>
    </select>

<?php
    
}

if($row['type'] == 'time')
{
    
?>
    <input type="text" name="variable[]" id="$row['id']" aria-required="true"  />

<?php
    
}


?>

</td>
<input type="hidden" name="variable_id[]" value="<?php echo $row['id']; ?>">

</td>

</tr>
</table>
</td>

<?php
    
}//end if(row)
else{
    //echo "<td>&nbsp;</td>";	//If there are no more records at the end, add a blank column
}


        }//end for (cols)
    }//end if($result)
} while($row);

//echo "</table>";

?>

    <tr>
    <td><input type="submit" value="<?php _e('report', 'buddypress' ); ?>" id="experiment-report-variables" name="report" />
    </td>
    </tr>
</tr>


</table>




</form>


<table>




<?php
    
    
    //SELECT DISTINCT(variable_id) as variable_id FROM `wp_bp_experiments_report` WHERE experiment_id=69;
    
    /*
     SELECT DISTINCT(wp_bp_experiments_variables.name) AS variable_name FROM wp_bp_experiments_variables
     INNER JOIN wp_bp_experiments_report ON wp_bp_experiments_variables.experiment_id=wp_bp_experiments_report.experiment_id AND wp_bp_experiments_variables.id=wp_bp_experiments_report.variable_id;
     
     */
    $experimentid = bp_get_current_experiment_id();
    //$query = "SELECT DISTINCT(wp_bp_experiments_variables.name) AS variable_name FROM wp_bp_experiments_variables INNER JOIN wp_bp_experiments_report ON wp_bp_experiments_variables.experiment_id=$experimentid AND wp_bp_experiments_variables.id=wp_bp_experiments_report.variable_id";
    $query="SELECT id, name, type FROM wp_bp_experiments_variables where wp_bp_experiments_variables.experiment_id=$experimentid";
    $result = mysql_query($query);
    
    $variableIds = array();
    
    echo "<tr>";
    
    
    do{
		$row=mysql_fetch_array($result);
        $variableIds[] = $row['id'];
        
        echo "<td width=33%><b>";
        echo $row['name'];
        echo "</td>";
        
    }while($row);
    
    echo "<td width=33%><b>";
    echo "Date/Time";
    echo "</td>";
    
    echo "</tr>";
    
    echo "<tr>";
    
    echo "<td width=33%>";
    
	$result1=mysql_query("select * from wp_bp_experiments_report where experiment_id=$experimentid and variable_id=$variableIds[0]");
    
    echo "<table>";
    do{
        $row=mysql_fetch_array($result1);
        
        echo "<tr>";
        echo "<td>";
        echo $row['variable_value'];
        echo "</td>";
        echo "</tr>";
        
    } while($row);
    echo "</table>";
    
    
    echo "</td>";
    
    echo "<td width=33%>";
    
	$result2=mysql_query("select * from wp_bp_experiments_report where experiment_id=$experimentid and variable_id=$variableIds[1]");
    
    echo "<table>";
    do{
        $row=mysql_fetch_array($result2);
        
        echo "<tr>";
        echo "<td>";
        echo $row['variable_value'];
        echo "</td>";
        echo "</tr>";
        
    } while($row);
    echo "</table>";
    echo "</td>";
    
    echo "<td width=33%>";
    
    $result2=mysql_query("select * from wp_bp_experiments_report where experiment_id=$experimentid and variable_id=$variableIds[1]");
    
    echo "<table>";
    do{
        $row=mysql_fetch_array($result2);
        
        echo "<tr>";
        echo "<td>";
        echo $row['date_modified'];
        echo "</td>";
        echo "</tr>";
        
    } while($row);
    echo "</table>";
    echo "</td>";
    
    echo "</tr>";
?>

</table>



<?php endif; ?>



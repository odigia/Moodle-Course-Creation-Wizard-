<?php 
$contextid = $_POST["contextid"];  
$courseid = $_POST["courseid"];  
?>
        <div class="placer">
                <div class="container">
                        
                        <div class="messagew">
                                Congratulations
                        </div>
                        <div class="contentw">
                                
                                <div class="row">
                                        <div class="textwrapper">
                                                <div class="text">
                                                        <b>Your course has now been created!</b>
                                                        <br><br>
                                                        Next we will start building the content of the course.                                                        
                                                </div>
                                        </div>  
                                </div>  

                                <div class="row">
                                        <div class="buttonswrapper">
                                                <div class="buttons">
                                                        <div class="button">
                                                                <a href="#" class="nyroModalClose" onclick="location.href='step8.php?courseid=<?php echo $courseid ?>&showfirstmessage=1'">Continue</a>
                                                        </div>
                                                        <br/>
                                                        <a href="#" class="nyroModalClose" onclick="location.href='step5.php?contextid=<?php echo $contextid ?>'">Cancel</a>
                                                </div>
                                        </div>
                                </div>
                        </div>
                </div>
        </div>
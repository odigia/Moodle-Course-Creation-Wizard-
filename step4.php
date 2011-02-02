<?php 
$contextid = isset($_REQUEST["contextid"])?$_REQUEST["contextid"]:0;
$courseid = isset($_REQUEST["courseid"])?$_REQUEST["courseid"]:0;
?>
        <div class="placer">
                <div class="container">
                        
                        <div class="messagew">
                                The Next Step
                        </div>
                        <div class="contentw">
                                
                                <div class="row">
                                        <div class="textwrapper">
                                                <div class="text">
                                                        Next we will assign Teachers/Instructors to the course.
                                                </div>
                                        </div>  
                                </div>  

                                <div class="row">
                                        <div class="buttonswrapper">
                                                <div class="buttons">
                                                        <div class="button">
                                                                <a href="#" class="nyroModalClose" onclick="location.href='step5.php?contextid=<?php echo $contextid ?>'">Continue</a>
                                                        </div>
                                                        <br/>
                                                        <a href="#" class="nyroModalClose" onclick="location.href='step3.php?id=<?php echo $courseid; ?>'">Cancel</a>
                                                </div>
                                        </div>
                                </div>

                        </div>
                        
                </div>
        </div>

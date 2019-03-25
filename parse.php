<?php
/**
 * Created by PhpStorm.
 * User: janbe
 * Date: 24/03/2019
 * Time: 17:10
 */
$a = null;
while($a = fgets(STDIN))
{
	fwrite(STDERR,"THIS IS A FAKE PARSE.PHP!!!\n");
	printf($a."\n");
}

return 0;
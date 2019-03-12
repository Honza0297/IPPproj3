<?php
/**
 * Created by PhpStorm.
 * User: janbe
 * Date: 07/03/2019
 * Time: 17:26
 */
$recursive = false;
$interpreter = false;
$parser = false;
$path_to_int = ".";
$path_to_par = ".";
$path_to_tests = ".";

function check_help($argv)
{
    if(count($argv) == 2 && $argv[1] == "--help")
    {
        print("Toto je napoveda pro skript test.php\n");
        print("Skript testuje skripty parse.php a interpret.php \n");
        print("Aplikace prijima nekolik parametru:\n");
        //TODO dodelat :)
        exit(0);
    }
}

function check_args($argv)
{
    if(count($argv) != 2 && in_array("--help", $argv))
    {
        return 10;
    }

    $only_parse = false;
    $only_int = false;
    $arg_values = array(
                "path_to_tests" => ".",
                "recursive" => false,
                "interpreter" => true,
                "parser" => true,
                "path_to_par"=>".",
                "path_to_int"=>".");
    foreach($argv as $arg)
    {
        if(substr($arg, 0, 11 ) == "--directory")
        {
            $arg_values["path_to_tests"] = substr($arg, 12);
        }

        if($arg == "--recursive")
        {
            $arg_values["recursive"] = true;
        }

        if($arg == "--int-only" && !$only_parse)
        {
            $only_int = true;
            $arg_values["parser"] = false;
        }
        else if($arg == "--int-only" && $only_parse)
        {
            return 10;
        }

        if($arg == "--parse-only" && !$only_int)
        {
            $only_parse = true;
            $arg_values["interpreter"] = false;
        }
        else if($arg == "--parse-only" && $only_int)
        {
            return 10;
        }

        if(substr($arg, 0, 14) == "--parse-script" && !$only_int)
        {
            $arg_values["path_to_par"] = substr($arg, 15);
        }
        else if(substr($arg, 0, 14) == "--parse-script" && $only_int)
        {
            return 10;
        }

        if(substr($arg, 0, 12) == "--int-script" && !$only_parse)
        {
            $arg_values["path_to_par"] = substr($arg, 13);
        }
        else if(substr($arg, 0, 12) == "--int-script" && $only_parse)
        {
            return 10;
        }
    }
    return $arg_values;
}

function get_tests($start,$recursive)
{
    if(strtoupper(substr(PHP_OS, 0,3)) == "LIN")
    {
        str_replace(" ", "\ ", $start);
    }
    $tests = array();
    $dir =  scandir($start);
    foreach($dir as $item)
    {
        if(preg_match("/.+[.]src/", $item, $src_test))
        {
            if(strtoupper(substr(PHP_OS, 0,3)) == "LIN")
            {
                str_replace(" ", "\ ", $src_test[0]);
            }
            array_push($tests,$start."/".$src_test[0]);
        }

        if($item != "." && $item != ".." && is_dir($item) && $recursive)
        {
            if(strtoupper(substr(PHP_OS, 0,3)) == "LIN")
            {
                str_replace(" ", "\ ", $item);
            }
            $new_path = $start."/".$item;
            $tests = array_merge($tests, get_tests($new_path, $recursive));
        }
    }
    return $tests;
}

function check_for_other_files($file)
{//pokud chybi in, out, dogeneruje se prazdny, pro rc s nulou
    $file_without_suffix = str_replace(".src", ".", $file);
    if(!file_exists($file_without_suffix."rc"))
    {
        $rc = fopen($file_without_suffix."rc", "w");
        fwrite($rc, "0");
        fclose($rc);
    }
    if(!file_exists($file_without_suffix."in"))
    {
        $in = fopen($file_without_suffix."in", "w");
        fclose($in);
    }
    if(!file_exists($file_without_suffix."out"))
    {
        $out = fopen($file_without_suffix."out", "w");
        fclose($out);
    }
}
function html_add_info(object $html_output, $test, $passed, $int_or_par)
{
    $test_name = preg_match("([^\/]+(?=.src$))", $test)[0];
    $msg = $test_name.": ".$int_or_par." ";
    if($passed)
    {
        $msg = $msg."OK";
    }
    else
    {
        $msg=$msg."FAIL";
    }
    /** @var object $new_record */
    $new_record = $html_output->createElement("p", $msg);//Create new <p> tag
    $attr = $html_output->createAttribute("style");
    if($passed)
    {
        $attr->value = "color: green";
    }
    else
    {
        $attr->value = "color: red";
    }
    $new_record->appendChild($attr);
    return $new_record;
}

//main body starts here
$html_output = new DOMDocument();
$html = $html_output->createElement("html");//Create new <br> tag
$html_output ->appendChild($html);//Add the <br> tag to document
$body = $html_output ->createElement("body");
$attr = $html_output->createAttribute("style");
$attr->value = "background-color: black";
$body->appendChild($attr);
$html->appendChild($body);

check_help($argv);
$args = check_args($argv);
if($args == 10)
{
    print("ERROR");
    exit($args);
}
$recursive = $args["recursive"];
$interpreter = $args["interpreter"];
$parser = $args["parser"];
$path_to_int = $args["path_to_int"];
$path_to_par = $args["path_to_par"];
$path_to_tests = $args["path_to_tests"];
$test_list = get_tests($path_to_tests, $recursive);


foreach($test_list as $test)
{
    check_for_other_files($test);
    if($parser)
    {
        exec("php7.3 ".$path_to_par."/parse.php <".$test, $output, $ret_val);
        if($interpreter)
        {
            $parser_output = "parser_output ";
        }
        else
        {
            //TODO compare
        }
    }

    if($interpreter)
    {
        if($parser)
        {
            $input_data = $parser_output;
        }
        else
        {
            $input_data = $test;
        }
        exec("python3 ".$path_to_par."/interpret.py <".$input_data, $output, $ret_val);

        $test_ok = false;
        //TODO comapre

        /** @var object $body */
        $body->appendChild(html_add_info($html_output, $test, $test_ok, "interpreter"));
        print($html_output->saveHTML());

    }
}
//src - zdroják v IPPcode
//rc - chybova hodnota
//in - vstup
//out - vystup
/*TODO
overit, jak dobre se to spousti
porovnat s referencnimi vystupy
overit html vystup*/


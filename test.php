<?php
/**
 * Created by PhpStorm.
 * User: janbe
 * Date: 07/03/2019
 * Time: 17:26
 */

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
            print("jsem tu?");
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

function check_for_other_files_and_generate_missing($file)
{//pokud chybi in, out, dogeneruje se prazdny, pro rc s nulou
    $file_without_suffix = str_replace(".src", ".", $file);
    printf("file without suffix: ".$file_without_suffix."\n");
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

function html_add_info(DOMDocument $html_output, $test, $passed, $int_or_par)
{
    if(preg_match("([^\/]+(?=.src$))", $test, $test_name))
        $test_name = $test_name[0]; //we want only the first (and only) match
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

function run_test($input_args, $path_to_test, object $html_output)
{
    if($input_args["parser"])
    {
        //WARNING zmenit na php7.3
        $command = "php ".$input_args["path_to_par"]."/parse.php <".$path_to_test;
        print("*********Executing command: ".$command."\n");
        exec($command, $parser_output, $parser_return_value);
        $parser_output = implode("\n",$parser_output);
        if(!$input_args["interpreter"])
        {
            $passed = true;
            $path_to_output = str_replace(".src", ".out", $path_to_test);
            $path_to_retval = str_replace(".src", ".rc", $path_to_test);

            $expected_output = file_get_contents($path_to_output);
            if(strcmp($parser_output, $expected_output) == 0)
            {
                $passed = false;
                printf("!!!!!!!!!!!!!!!something is wrong.\n");
                printf("Expected output:\n".$expected_output);
                printf("Parser output\n".$parser_output."\n");
            }

            $expected_retval = file_get_contents($path_to_retval);
            if($parser_return_value != $expected_retval)
            {
                $passed = false;
                printf("!!!!!!!!!!!!!!!something is wrong.\n");
                printf("Expected retval:\n".$expected_retval);
                printf("Parser retval\n".$parser_return_value."\n");
            }
            print("*************".gettype($html_output));
            $html_output->firstChild->firstChild->appendChild(html_add_info($html_output, $path_to_test, $passed, "parser"));
        }
    }
    if($input_args["interpreter"])
    {
        if($input_args["parser"])
        {
            $input_file = fopen(str_replace(".src", ".xml", $path_to_test), "w");
            fwrite($input_file, $parser_output);
            fclose($input_file);
            $interpreter_source =str_replace(".src", ".xml", $path_to_test);
            $int_or_par = "both";
        }
        else
        {
            $interpreter_source = $path_to_test;
            $int_or_par = "interpreter";
        }

        $interpreter_input = str_replace(".src", ".in", $path_to_test);
        //warning change to python3
        $command = "python ".$input_args["path_to_int"]."/interpret.py --source=".$interpreter_source." --input=".$interpreter_input;
        printf("********Executing command: ".$command);
        exec($command, $interpreter_output, $interpreter_return_value);
        $interpreter_output = implode("\n",$interpreter_output);
        $passed = true;
        $path_to_output = str_replace(".src", ".out", $path_to_test);
        $path_to_retval = str_replace(".src", ".rc", $path_to_test);

        $expected_output = file_get_contents($path_to_output);
        if($interpreter_output != $expected_output)
        {
            $passed = false;
            printf("!!!!!!!!!!!!!!!something is wrong.\n");
            printf("Expected output:\n".$expected_output);
            printf("inter output\n".$interpreter_output."\n");
        }

        $expected_retval = file_get_contents($path_to_retval);
        if($interpreter_return_value != $expected_retval)
        {
            $passed = false;
            printf("!!!!!!!!!!!!!!!something is wrong.\n");
            printf("Expected output:\n".$expected_retval);
            printf("inter output\n".$interpreter_return_value."\n");
        }
        $html_output->firstChild->firstChild->appendChild(html_add_info($html_output, $path_to_test, $passed, $int_or_par));
    /*run interpreter. if parser was ran too, redirect output from parser to interpreter via stdin - hope*/
    }
    //$html_output->appendChild(html_add_info($html_output, $path_to_test, true, "both"));
}
//main body starts here
/*HTMLstart*/
$html_output = new DOMDocument();
$html = $html_output->createElement("html");//Create new <br> tag
$html_output ->appendChild($html);//Add the <br> tag to document
$body = $html_output ->createElement("body");
$attr = $html_output->createAttribute("style");
$attr->value = "background-color: black";
$body->appendChild($attr);
$body = $html->appendChild($body);
/*HTML end*/

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

print("path to test: ".$path_to_tests."\n");
$Directory = new RecursiveDirectoryIterator($path_to_tests);
if($recursive) $Directory = new RecursiveIteratorIterator($Directory);
while($Directory->valid())
{

    $path_name = $Directory->getSubPathName();
    $path_name = $path_to_tests."/".$path_name;
    if(preg_match("/^.*src$/", $path_name))
    {
        print("Test found: ".$path_name."\n");
        check_for_other_files_and_generate_missing($path_name); /*WARNING to lomitko muze zlobit*/
        run_test($args, $path_name, $html_output);
    }
    $Directory->next();
}
print(str_replace("><", ">\n<",$html_output->saveHTML()));
$file = fopen("output.html", "w");
fwrite($file,str_replace("><", ">\n<",$html_output->saveHTML()) );
exit(-654);
//src - zdroják v IPPcode/XML
//rc - chybova hodnota
//in - vstup pro interpretaci. NE ZDROJAK
//out - vystup
/*TODO
Pripravit testy
Test s fake parse a interpreter
opravit zlé vytváření HTML
*/


<?php

// Требуется закоментировать строчку в MARCBASE.php
// $this->xmlwriter->startDocument('1.0', 'UTF-8');

require 'vendor/autoload.php';
require 'File/MARC.php';
use diversen\parseArgv;
$params = new parseArgv();
$key_params = $params->flags;
$value_params = $params->valuesByKey;

if ( !count($value_params) > 0 )
{
    print_usage();
    exit();
}
$h_file_in = fopen($value_params[0], "r");
if (! $h_file_in)
{
    echo "Cannot open file ". $value_params[0]. " to read\n";
    exit(0);
}
if ( array_key_exists('t', $key_params) )
{
    if (! is_numeric($key_params['t']))
    {
        print_usage();
        exit();
    } 
} else {
    $key_params['t'] = 700;
}
if ( array_key_exists('e', $key_params) )
{
    $key_params['e'] = strtolower($key_params['e']);
    if (! in_array($key_params['e'], ['utf8', 'cp1251']) )
    {
        print_usage();
        exit();
    } 
} else {
    $key_params['e'] = 'utf8';
}
if ( array_key_exists('f', $key_params) )
{
    $key_params['f'] = strtolower($key_params['f']);
    if (! in_array($key_params['f'], ['mrc', 'xml', 'json']) )
    {
        print_usage();
        exit();
    } 
} else {
    $key_params['f'] = 'mrc';
}
if ( array_key_exists('i', $key_params) )
{
    $key_params['i'] = strtolower($key_params['i']);
    if (! in_array($key_params['i'], ['yes', 'no']) )
    {
        print_usage();
        exit();
    } 
} else {
    $key_params['i'] = 'no';
}
$h_file_out = null;
$out_uri = ( count($value_params) > 1 )? $value_params[1] : "php://stdout";
if ($key_params['f'] == 'xml')
{
    $h_file_out = new XMLWriter();
    if (! $h_file_out->openUri($out_uri) )
    {
        echo "Cannot open URI ". $out_uri. " to write\n";
        exit(0);
    }
    $h_file_out->setIndent(true);
    $h_file_out->startDocument('1.0', 'UTF-8');
    $h_file_out->startElement('collection');
} 
else 
{
    $h_file_out = fopen($out_uri, "w");
    if (! $h_file_out )
    {
        echo "Cannot open file ". $out_uri. " to write\n";
        exit(0);
    }     
}

$cur_line_no = 0;
$cur_record = null;
while (!feof($h_file_in))
{
    $cur_line_no++;
    $cur_line = ( $key_params['e'] == 'cp1251' )?
            iconv('Windows-1251', 'UTF-8//IGNORE', fgets($h_file_in)) : fgets($h_file_in);
    $m = preg_match("/^#(\d{1,3}): (.+)[\r\n]*$/U", $cur_line, $matches);
    if ( $m > 0 )
    {
        if ( is_null($cur_record) ) 
        {
            $cur_record = new File_MARC_Record();
            $leader = "     n    22      a 450 ";
            $cur_record->setLeader($leader);
        }
        $field_tag = str_pad ($matches[1], 3, "0", STR_PAD_LEFT);
        $raw_field = $matches[2];        
        if ( ord(substr($raw_field, 2, 1)) == 31 )
        {
            $raw_subfields = explode(chr(31), $raw_field);
            $subfields = [];
            for ( $i = 1; $i < count($raw_subfields); $i++ )
            {
                $raw_subfield = ( strlen($raw_subfields[$i]) > intval($key_params['t']) )?
                    shorten($raw_subfields[$i], intval($key_params['t'])) : $raw_subfields[$i];                            
                $subfield = new File_MARC_Subfield(substr($raw_subfield, 0, 1), substr($raw_subfield, 1));
                array_push($subfields, $subfield);
            }
            $field = new File_MARC_Data_Field($field_tag, $subfields, substr($raw_subfields[0], 0, 1), substr($raw_subfields[0], 1, 1));
        } 
        else 
        {
            $field = new File_MARC_Control_Field($field_tag, $raw_field);
        }
        $cur_record->appendField($field);
    } else {
        if ( !is_null($cur_record) ) 
        {    
            if ($key_params['f'] == 'xml')
            {
                $h_file_out->writeRaw($cur_record->toXML('UTF-8', $key_params['i'] == 'yes' , false));
                // $h_file_out->flush();
            } elseif ($key_params['f'] == 'mrc')
            {
                fwrite($h_file_out, $cur_record->toRaw());
            } elseif ($key_params['f'] == 'json')
            {
                fwrite($h_file_out, $cur_record->toJSON());
            }
            $cur_record = null;
        }
    }
}
fclose($h_file_in);
if ($key_params['f'] == 'xml')
{
    $h_file_out->endElement();
    $h_file_out->endDocument();
    $h_file_out->flush();
}
else 
{
    fclose($h_file_out);
}

function print_usage()
{
   echo "Irbis TXT to Marc ISO convertor\n";
   echo "Copyright (c) 2017 Lobachevsky library group RK KFU\n";
   echo "Usage: convert.php [-fiet] input.file [output.file]\n";
   echo "  -f=MRC|XML|JSON: records output format.  Default: MRC\n";
   echo "  -i=Yes|No: use or not ident offset levels in XML. Default: No\n";
   echo "  -e=utf8|cp1251: encoding of input file. Default: utf8\n";
   echo "  -t=<num bytes>: truncate fields longer than num bytes. Default: 700\n";
}

function shorten($long_string, $size)
{    
    $s1 = strtok(wordwrap($long_string, $size - 3, "\n", true), "\n");
    $s2 = @iconv('UTF-8', 'UTF-8//IGNORE', $s1);
    return trim($s2, ";:., \t\n\r\0\x0B").' ...';
}

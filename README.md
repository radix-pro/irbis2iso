# irbis2iso

Irbis TXT to Marc ISO convertor

Copyright (c) 2017 Lobachevsky library group RK KFU

Usage: convert.php [-fiet] input.file [output.file]

   -f=MRC|XML|JSON: records output format.  Default: MRC
   
   -i=Yes|No: use or not ident offset levels in XML. Default: No
   
   -e=utf8|cp1251: encoding of input file. Default: utf8
   
   -t=<num bytes>: truncate fields longer than num bytes. Default: 700
   
Example:

C:> php convert.php -f=XML -i=Yes -e=utf8 records.txt > records.xml


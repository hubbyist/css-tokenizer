@conversion-1: 5cm + 10mm;
@conversion-2: 2 - 3cm - 5mm;
============TEST
T_ATWORD,1:1,1:13,"@conversion-1"
T_COLON,1,14,":"
T_WHITESPACE,1,15," "
T_WORD,1:1,16:18,"5cm"
T_WHITESPACE,1,19," "
T_WORD,1:1,20:20,"+"
T_WHITESPACE,1,21," "
T_WORD,1:1,22:25,"10mm"
T_SEMICOLON,1,26,";"
T_WHITESPACE,2,0,"\n"
T_ATWORD,2:2,1:13,"@conversion-2"
T_COLON,2,14,":"
T_WHITESPACE,2,15," "
T_WORD,2:2,16:16,"2"
T_WHITESPACE,2,17," "
T_WORD,2:2,18:18,"-"
T_WHITESPACE,2,19," "
T_WORD,2:2,20:22,"3cm"
T_WHITESPACE,2,23," "
T_WORD,2:2,24:24,"-"
T_WHITESPACE,2,25," "
T_WORD,2:2,26:28,"5mm"
T_SEMICOLON,2,29,";"
<div{+START,IF_PASSED,CLASS} class="{CLASS*}"{+END}{+START,IF_PASSED,ID} id="{ID*}"{+END}{+START,IF,{$NOT,{$MOBILE}}} style="{+START,IF_NON_EMPTY,{PADDING}}{$,padding acts weird in IE6}margin{PADDING*}: {PADDING_AMOUNT*}%; {+END}float: {FLOAT*}{+START,IF_NON_EMPTY,{WIDTH}}; width: {WIDTH*'}{+END}"{+END}>
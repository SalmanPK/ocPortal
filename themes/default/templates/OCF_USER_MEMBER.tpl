{+START,IF,{$NOT,{FIRST}}}, {+END}{+START,IF_PASSED,IF_PASSED_AND_TRUE}<em>{+END}{+START,IF_PASSED,AT}<a {+START,IF_PASSED,COLOUR}class="{COLOUR*}" {+END}title="{USERNAME*}: {!LAST_VIEWED}&hellip; {AT#}" href="{PROFILE_URL*}">{$DISPLAYED_USERNAME*,{USERNAME}}</a>{+END}{+START,IF_NON_PASSED,AT}<a {+START,IF_PASSED,COLOUR}class="{COLOUR*}" {+END}title="{USERNAME*}: {+START,IF_PASSED,USERGROUP}{USERGROUP*}{+END}{+START,IF_NON_PASSED,USERGROUP}{!MEMBER}{+END}" href="{PROFILE_URL*}">{$DISPLAYED_USERNAME*,{USERNAME}}</a>{+END}{+START,IF_PASSED,IF_PASSED_AND_TRUE}</em>{+END}{+START,IF_PASSED,AGE} ({AGE*}){+END}

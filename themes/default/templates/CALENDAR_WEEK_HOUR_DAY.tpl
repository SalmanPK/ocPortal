{+START,IF_NON_EMPTY,{ADD_URL}}
<td onkeypress="if (enter_pressed(event)) this.onclick(event);" onmouseover="this.className=this.className+' calendar_hover';" onmouseout="this.className=this.className.replace(/ calendar\_hover/,'');" onclick="window.location.href='{ADD_URL*;}'" class="calendar_{CLASS*}{+START,IF,{CURRENT}} calendar_current{+END}" rowspan="{DOWN*}">
	{ENTRIES}
</td>
{+END}
{+START,IF_EMPTY,{ADD_URL}}
<td class="calendar_{CLASS*}{+START,IF,{CURRENT}} calendar_current{+END}" rowspan="{DOWN*}">
	{ENTRIES}
</td>
{+END}


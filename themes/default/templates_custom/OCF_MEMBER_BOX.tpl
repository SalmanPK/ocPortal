{+START,IF,{GIVE_CONTEXT}}
<div class="box"><div class="box_inner">
{+END}
{+START,IF,{$NOT,{GIVE_CONTEXT}}}
<div class="ocf_member_box">
{+END}
	{+START,IF,{GIVE_CONTEXT}}
		<h3>{!CONTENT_IS_OF_TYPE,{!MEMBER},{$USERNAME*,{MEMBER_ID}}}</h3>
	{+END}

	{$SET,fancy_screen,{$AND,{$NOT,{$MATCH_KEY_MATCH,site:members:view}},{$OR,{$RUNNING_SCRIPT,iframe},{$MATCH_KEY_MATCH,site:members},{$MATCH_KEY_MATCH,site:search}}}}

	{+START,IF,{$GET,fancy_screen}}
		{+START,SET,ADDITIONAL_DETAILS}
			{+START,IF,{$NOT,{$MATCH_KEY_MATCH,_WILD:galleries}}}
				{+START,IF_PASSED,WARNINGS}
					<tr><th class="de_th">{!MODULE_TRANS_NAME_warnings}:</th><td>{WARNINGS*}</td></tr>
				{+END}
				{+START,IF_PASSED,GALLERIES}
					<tr><th class="de_th">{!galleries:GALLERIES}:</th><td>{GALLERIES*}</td></tr>
				{+END}
				{+START,IF_PASSED,AGE}
					<tr><th class="de_th">{!AGE}:</th><td><strong>{AGE*}</strong> ({DATE_OF_BIRTH*})</td></tr>
				{+END}
			{+END}
			<tr>
				<th class="de_th">{!USERGROUPS}:</th>
				<td>{+START,LOOP,OTHER_USERGROUPS}{+START,IF,{$NEQ,{_loop_key},0}}, {+END}{_loop_var*}{+END}</td>
			</tr>
		{+END}

		{+START,SET,CONTENTS}
			{$SET,AVATAR_URL,{$?,{$GET,fancy_screen},{$THUMBNAIL,{$?,{$IS_EMPTY,{$PHOTO,{MEMBER_ID}}},{$IMG,no_image},{$PHOTO,{MEMBER_ID}}},80x80,uploads/website_specific,,,pad,both,#faf5ef},{AVATAR_URL}}}

			{$SET,main,0}
			<a onmouseover="if (typeof window.activate_tooltip!='undefined') activate_tooltip(this,event,'&lt;table class=&quot;tooltip_fields autosized_table&quot; summary=&quot;{!MAP_TABLE}&quot;&gt;&lt;tbody&gt;{CUSTOM_FIELDS_FULL*;^}{$GET*;^,ADDITIONAL_DETAILS}&lt;/tbody&gt;&lt;/table&gt;','auto');" href="{$MEMBER_PROFILE_URL*,{MEMBER_ID}}"><img class="ocf_member_box_avatar" src="{$GET*,AVATAR_URL}" alt="" /></a>

			<div{+START,IF_NON_EMPTY,{$GET,AVATAR_URL}} class="ocf_member_box_avatar"{+END} style="float: left; width: 95px; word-wrap: break-word; margin-right: 0">
				<a href="{$MEMBER_PROFILE_URL*,{MEMBER_ID}}">{$TRUNCATE_LEFT,{$USERNAME,{MEMBER_ID}},18,1}</a>
				{$SET,main,1}
				{CUSTOM_FIELDS}

				{+START,IF,{$ADDON_INSTALLED,ocGiftGiver,1}}
					<p class="associated_link">
						{+START,IF,{$NEQ,{$MEMBER},{MEMBER_ID}}}
							<a href="{$PAGE_LINK*,site:pointstore:action:ocgifts:username={$USERNAME,{MEMBER_ID}}}">{!ocgifts:GIVE_A_GIFT}</a>
						{+END}
						{+START,IF,{$EQ,{$MEMBER},{MEMBER_ID}}}
							<em>{!ocgifts:THIS_IS_YOU}</em>
						{+END}
					</p>
				{+END}
			</div>
		{+END}

		{+START,IF,{$NOT,{GIVE_CONTEXT}}}
		<div class="ocf_member_directory_box box">
		{+END}
			<input class="right" type="hidden" name="result__member_{MEMBER_ID*}" value="1" />

			{$GET,CONTENTS}
		{+START,IF,{$NOT,{GIVE_CONTEXT}}}
		</div>
		{+END}
	{+END}

	{+START,IF,{$NOT,{$GET,fancy_screen}}}
		{+START,IF_NON_EMPTY,{AVATAR_URL}}
			<img class="ocf_member_box_avatar" src="{AVATAR_URL*}" alt="{!SPECIFIC_AVATAR,{$USERNAME*,{MEMBER_ID}}}" title="{!SPECIFIC_AVATAR,{$USERNAME*,{MEMBER_ID}}}" />
		{+END}

		<table class="tooltip_fields autosized_table" summary="{!MAP_TABLE}">
			<tbody>
				<tr><th class="de_th">{!USERNAME}:</th><td><a href="{$MEMBER_PROFILE_URL*,{MEMBER_ID}}">{$USERNAME*,{MEMBER_ID}}</a></td></tr>
				<tr><th class="de_th">{!ocf:SPECIFIC_FORUM_POSTS}:</th><td>{POSTS*}</td></tr>
				{+START,IF_NON_EMPTY,{POINTS}}
					<tr><th class="de_th">{!POINTS}:</th><td>{POINTS*}</td></tr>
				{+END}
				<tr><th class="de_th">{!JOINED}:</th><td>{JOIN_DATE*}</td></tr>
				{+START,IF,{$NOT,{$MATCH_KEY_MATCH,_WILD:galleries}}}
					{+START,IF_PASSED,IP_ADDRESS}
						<tr><th class="de_th">{!IP_ADDRESS}:</th><td>{$TRUNCATE_LEFT,{IP_ADDRESS},15,1}</td></tr>
					{+END}
					{+START,IF_PASSED,WARNINGS}
						<tr><th class="de_th">{!MODULE_TRANS_NAME_warnings}:</th><td>{WARNINGS*}</td></tr>
					{+END}
					{+START,IF_PASSED,GALLERIES}
						<tr><th class="de_th">{!galleries:GALLERIES}:</th><td>{GALLERIES*}</td></tr>
					{+END}
					{+START,IF_PASSED,DATE_OF_BIRTH}
						<tr><th class="de_th">{!DATE_OF_BIRTH}:</th><td>{DATE_OF_BIRTH*}</td></tr>
					{+END}
				{+END}
				<tr>
					<th class="de_th">{!USERGROUPS}:</th>
					<td>{+START,LOOP,OTHER_USERGROUPS}{+START,IF,{$NEQ,{_loop_key},0}}, {+END}{_loop_var*}{+END}</td>
				</tr>
				<tr><th class="de_th">{!ONLINE_NOW}:</th><td>{$?*,{ONLINE},{!YES},{!NO}}</td></tr>
				{CUSTOM_FIELDS}
			</tbody>
		</table>
	{+END}
{+START,IF,{$NOT,{GIVE_CONTEXT}}}
</div>
{+END}
{+START,IF,{GIVE_CONTEXT}}
</div></div>
{+END}
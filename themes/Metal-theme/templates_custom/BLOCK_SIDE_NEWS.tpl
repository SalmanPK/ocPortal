{+START,BOX,{TITLE},,{$?,{$GET,in_panel},panel,classic},,,{$?,{$IS_NON_EMPTY,{ARCHIVE_URL}},<a rel="archives" href="{ARCHIVE_URL*}">More</a>|}{$?,{$IS_NON_EMPTY,{SUBMIT_URL}},<a rel="add" href="{SUBMIT_URL*}">Add</a>|}}
	{+START,IF_EMPTY,{CONTENT}}
		<p class="block_no_entries">&raquo; {$?,{BLOG},{!BLOG_NO_NEWS},{!NO_NEWS}}</p>
	{+END}
	{+START,IF_NON_EMPTY,{CONTENT}}
		{CONTENT}
	{+END}
{+END}

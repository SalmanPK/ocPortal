<div class="box1">
	{+START,IF_NON_EMPTY,{TITLE}}
		<h2 class="standardbox_title_panel">
			{TITLE}
		</h2>
	{+END}

	{+START,IF_NON_EMPTY,{META}}
		<div class="standardbox_meta_classic">
			{+START,LOOP,META}
				<div>{KEY}: {VALUE}</div>
			{+END}
		</div>
	{+END}

	{CONTENT}

	{+START,IF_NON_EMPTY,{LINKS}}
		<div class="{$?,{$IS_EMPTY,{TITLE}},standardbox_nt_panel,standardbox_t_panel} standardbox_links_classic community_block_tagline">
			{+START,LOOP,LINKS}
				{_loop_var}
			{+END}
		</div>
	{+END}
</div>

{strip}
{formfeedback hash=$feedback}
{form}
	{legend legend="Generic Settings"}
		<input type="hidden" name="page" value="{$page}" />
		{foreach from=$formStarsOptions key=item item=output}
			<div class="control-group">
				{formlabel label=$output.label for=$item}
				{forminput}
					{if $output.type == 'numeric'}
						{html_options name="$item" values=$numbers output=$numbers selected=$gBitSystem->getConfig($item) labels=false id=$item}
					{elseif $output.type == 'input'}
						<input type='text' name="{$item}" id="{$item}" value="{$gBitSystem->getConfig($item)}" />
					{else}
						{html_checkboxes name="$item" values="y" checked=$gBitSystem->getConfig($item) labels=false id=$item}
					{/if}
					{formhelp note=$output.note page=$output.page}
				{/forminput}
			</div>
		{/foreach}

		<div class="control-group">
			{formlabel label="Rating Names"}
			{forminput}
				<input type="text" name="stars_rating_names" value="{$gBitSystem->getConfig('stars_rating_names')}" size="50" /><br />
				{formhelp note="Comma separated list of rating names.  Example: bad,better,best  Default is: Rating: 1, Rating: 2, ...  These names pop up when the mouse hovers over the corresponding star."}
			{/forminput}
		</div>

		<div class="control-group">
			{formlabel label="Icon Dimensions"}
			{forminput}
				{tr}Width{/tr}: <input type="text" name="stars_icon_width" value="{$gBitSystem->getConfig('stars_icon_width')}" size="5" /> {tr}pixels{/tr}<br />
				{tr}Height{/tr}: <input type="text" name="stars_icon_height" value="{$gBitSystem->getConfig('stars_icon_height')}" size="5" /> {tr}pixels{/tr}
				{formhelp note="Please enter the width and height of a single star."}
			{/forminput}
		</div>

		<div class="control-group">
			{formlabel label="Ratable Content"}
			{forminput}
				{html_checkboxes options=$formRatable.guids value=y name=ratable_content separator="<br />" checked=$formRatable.checked}
				{formhelp note="Here you can select what content can be rated."}
			{/forminput}
		</div>

		<div class="control-group">
			{formlabel label="Rated Content" for=""}
			{forminput}
				{smartlink ititle="View a list of rated content" ipackage=stars ifile="index.php"}
			{/forminput}
		</div>
	{/legend}

	{legend legend="Version Rating"}
		{foreach from=$formStarsVersion key=item item=output}
			<div class="control-group">
				{formlabel label=$output.label for=$item}
				{forminput}
					{if $output.type == 'numeric'}
						{html_options name="$item" values=$numbers output=$numbers selected=$gBitSystem->getConfig($item) labels=false id=$item}
					{elseif $output.type == 'input'}
						<input type='text' name="{$item}" id="{$item}" value="{$gBitSystem->getConfig($item)}" />
					{else}
						{html_checkboxes name="$item" values="y" checked=$gBitSystem->getConfig($item) labels=false id=$item}
					{/if}
					{formhelp note=$output.note page=$output.page}
				{/forminput}
			</div>
		{/foreach}
	{/legend}

	{legend legend="Weighting"}
		{formhelp note="You can influence how much importance is put on either of the following values when a user rates content.<br />If you don't want to use a particular one, just set it to 0."}
		{foreach from=$formStarsWeight key=item item=output}
			<div class="control-group">
				{formlabel label=$output.label for=$item}
				{forminput}
					{if $output.type == 'numeric'}
						{html_options name="$item" values=$numbers output=$numbers selected=$gBitSystem->getConfig($item) labels=false id=$item}
					{else}
						{html_checkboxes name="$item" values="y" checked=$gBitSystem->getConfig($item) labels=false id=$item}
					{/if}
					{formhelp note=$output.note page=$output.page}
				{/forminput}
			</div>
		{/foreach}

		<div class="control-group">
			<label class="checkbox">
				<input type="checkbox" name="recalculate" id="recalculate" />Re-caclulate Ratings
				{formhelp note="You can force a re-calculation of the entire rating database. this will update the users weighting with your current settings and will re-evaluate all rated objects."}
			</label>
		</div>

		<div class="control-group submit">
			<input type="submit" class="btn" name="stars_preferences" value="{tr}Change preferences{/tr}" />
		</div>
	{/legend}
{/form}
{/strip}

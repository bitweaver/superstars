{strip}
{formfeedback hash=$feedback}
{form}
	{legend legend="Generic Settings"}
		<input type="hidden" name="page" value="{$page}" />
		{foreach from=$formStarsOptions key=item item=output}
			<div class="row">
				{formlabel label=`$output.label` for=$item}
				{forminput}
					{if $output.type == 'numeric'}
						{html_options name="$item" values=$numbers output=$numbers selected=$gBitSystem->getConfig($item) labels=false id=$item}
					{elseif $output.type == 'input'}
						<input type='text' name="{$item}" id="{$item}" value="{$gBitSystem->getConfig(stars_auto_hide,0)}" />
					{else}
						{html_checkboxes name="$item" values="y" checked=$gBitSystem->getConfig($item) labels=false id=$item}
					{/if}
					{formhelp note=`$output.note` page=`$output.page`}
				{/forminput}
			</div>
		{/foreach}

		<div class="row">
			{formlabel label="Icon Dimensions"}
			{forminput}
				{tr}width{/tr}: <input type="text" name="stars_icon_width" value="{$gBitSystem->getConfig('stars_icon_width')}" size="5" /> pixel<br />
				{tr}height{/tr}: <input type="text" name="stars_icon_height" value="{$gBitSystem->getConfig('stars_icon_height')}" size="5" /> pixel
				{formhelp note="Please enter the width and height of a single stars icon."}
			{/forminput}
		</div>

		<div class="row">
			{formlabel label="Ratable Content"}
			{forminput}
				{html_checkboxes options=$formRatable.guids value=y name=ratable_content separator="<br />" checked=$formRatable.checked}
				{formhelp note="Here you can select what content can be rated."}
			{/forminput}
		</div>
	{/legend}

	{legend legend="Weighting"}
		{formhelp note="You can influence how much importance is put on either of the following values when a user rates content.<br />If you don't want to use a particular one, just set it to 0."}
		{foreach from=$formStarsPoints key=item item=output}
			<div class="row">
				{formlabel label=`$output.label` for=$item}
				{forminput}
					{if $output.type == 'numeric'}
						{html_options name="$item" values=$numbers output=$numbers selected=$gBitSystem->getConfig($item) labels=false id=$item}
					{else}
						{html_checkboxes name="$item" values="y" checked=$gBitSystem->getConfig($item) labels=false id=$item}
					{/if}
					{formhelp note=`$output.note` page=`$output.page`}
				{/forminput}
			</div>
		{/foreach}

		<div class="row">
			{formlabel label="Re-caclulate Ratings" for=recalculate}
			{forminput}
				<input type="checkbox" name="recalculate" id="recalculate" />
				{formhelp note="You can force a re-calculation of the entire rating database. this will update the users weighting with your current settings and will re-evaluate all rated objects."}
			{/forminput}
		</div>

		<div class="row submit">
			<input type="submit" name="stars_preferences" value="{tr}Change preferences{/tr}" />
		</div>
	{/legend}
{/form}
{/strip}

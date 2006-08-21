{strip}
{if $serviceHash.stars_load}
	{if $gBitSystem->isFeatureActive( 'stars_use_ajax' )}
		<script type="text/javascript">/*<![CDATA[*/ show_spinner('spinner'); /*]]>*/</script>
	{/if}
	<div class="stars-container" id="stars-{$serviceHash.content_id}">
		<table>
		<tr>
		<td>
			<ul class="stars-rating">
				<li class="stars-current" style="width:{$serviceHash.stars_version_pixels|default:0}px;">{if $serviceHash.stars_user_pixels}{tr}Your rating:{/tr} {$serviceHash.stars_user_rating} / {$gBitSystem->getConfig('stars_used_in_display')}{else}{tr}Rate{/tr}{/if}</li>
				{if (!$serviceHash.stars_version_user_rating || $gBitSystem->isFeatureActive( "stars_rerating" )) && $gBitUser->isRegistered()}
					{foreach from=$starsLinks item=k key=rate}
						<li>
							{if !$gBitUser->isRegistered()}
								<a class="stars-{$rate}" href="{$smarty.const.USERS_PKG_URL}login.php">{tr}You need to log in to rate{/tr}</a>
							{elseif $gBitSystem->isFeatureActive( 'stars_use_ajax' )}
								<a class="stars-{$rate}" href="javascript:ajax_updater( 'stars-{$serviceHash.content_id}', '{$smarty.const.STARS_PKG_URL}rate.php', 'content_id={$serviceHash.content_id}&amp;stars_rating={$rate}' );" title="{tr}Rating{/tr}: {$rate}">{$rate}</a>
							{else}
								<a class="stars-{$rate}" href="{$smarty.const.STARS_PKG_URL}rate.php?content_id={$serviceHash.content_id}&amp;stars_rating={$rate}" title="{tr}Rating{/tr}: {$rate}">{$rate}</a>
							{/if}
						</li>
					{/foreach}
				{/if}
			</ul>
		</td>
		<td>
			<ul class="stars-rating" style="margin: auto; margin-right:0px;">
				<li class="stars-current" style="width:{$serviceHash.stars_pixels|default:0}px;">{if $serviceHash.stars_user_pixels}{tr}Your rating:{/tr} {$serviceHash.stars_user_rating} / {$gBitSystem->getConfig('stars_used_in_display')}{else}{tr}Rate{/tr}{/if}</li>
				{if (!$serviceHash.stars_user_rating || $gBitSystem->isFeatureActive( "stars_rerating" )) && $gBitUser->isRegistered()}
					{foreach from=$starsLinks item=k key=rate}
						<li>
							{if !$gBitUser->isRegistered()}
								<a class="stars-{$rate}" href="{$smarty.const.USERS_PKG_URL}login.php">{tr}You need to log in to rate{/tr}</a>
							{elseif $gBitSystem->isFeatureActive( 'stars_use_ajax' )}
								<a class="stars-{$rate}" href="javascript:ajax_updater( 'stars-{$serviceHash.content_id}', '{$smarty.const.STARS_PKG_URL}rate.php', 'content_id={$serviceHash.content_id}&amp;stars_rating={$rate}' );" title="{tr}Rating{/tr}: {$rate}">{$rate}</a>
							{else}
								<a class="stars-{$rate}" href="{$smarty.const.STARS_PKG_URL}rate.php?content_id={$serviceHash.content_id}&amp;stars_rating={$rate}" title="{tr}Rating{/tr}: {$rate}">{$rate}</a>
							{/if}
						</li>
					{/foreach}
				{/if}
			</ul>
		</td>
		</tr>
		<tr>
		<td>
			{if $type != 'mini'}
				<small>
					{if $serviceHash.stars_version_rating}
							{math equation="rating * stars / 100" stars=$gBitSystem->getConfig('stars_used_in_display') rating=$serviceHash.stars_version_rating format="%.1f"} / {$gBitSystem->getConfig('stars_used_in_display')} {tr}in {$serviceHash.stars_version_rating_count} votes{/tr}
						{if $serviceHash.stars_user_rating} &nbsp;&bull;&nbsp; {tr}Your rating:{/tr} {$serviceHash.stars_version_user_rating|round} / {$gBitSystem->getConfig('stars_used_in_display')}{/if}
					{else}
						{tr}Waiting for {$gBitSystem->getConfig('stars_minimum_ratings',5)} ratings{/tr}
						{if $serviceHash.stars_user_rating} &nbsp;&bull;&nbsp; {tr}Your rating:{/tr} {$serviceHash.stars_version_user_rating|round} / {$gBitSystem->getConfig('stars_used_in_display')}{/if}
					{/if}
				</small>
			{/if}
		</td>
		<td style="text-align: right">
			{if $type != 'mini'}
				<small>
					{if $serviceHash.stars_rating}
						{math equation="rating * stars / 100" stars=$gBitSystem->getConfig('stars_used_in_display') rating=$serviceHash.stars_rating format="%.1f"} / {$gBitSystem->getConfig('stars_used_in_display')} {tr}in {$serviceHash.stars_rating_count} votes{/tr}
						{if $serviceHash.stars_user_rating} &nbsp;&bull;&nbsp; {tr}Your rating:{/tr} {$serviceHash.stars_user_rating|round} / {$gBitSystem->getConfig('stars_used_in_display')}{/if}
					{else}
						{tr}Waiting for {$gBitSystem->getConfig('stars_minimum_ratings',5)} ratings{/tr}
						{if $serviceHash.stars_user_rating} &nbsp;&bull;&nbsp; {tr}Your rating:{/tr} {$serviceHash.stars_user_rating|round} / {$gBitSystem->getConfig('stars_used_in_display')}{/if}
					{/if}
				</small>
			{/if}
		</td>
		</tr>
		</table>
	</div>
	{formfeedback hash=$starsfeed}
{/if}
{/strip}
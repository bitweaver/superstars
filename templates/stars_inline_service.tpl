{strip}
{if $serviceHash.stars_rating}
	{math equation="rating * stars / 100" stars=$gBitSystem->getConfig('stars_used_in_display') rating=$serviceHash.stars_rating format="%.1f" assign=current}
{/if}
{if $loadStars}
	<script type="text/javascript">/*<![CDATA[*/ show_spinner('spinner'); /*]]>*/</script>
	<div class="stars-container" id="stars-{$serviceHash.content_id}">
		<ul class="stars-rating">
			<li class="stars-current" style="width:{$serviceHash.stars_pixels|default:0}px;">{if !$serviceHash.stars_user_pixels and $gBitUser->isRegistered() && $gBitUser->mUserId != $serviceHash.user_id}{tr}Rate{/tr}{else}{tr}{tr}Your rating:{/tr} {$serviceHash.stars_user_rating} / {$gBitSystem->getConfig('stars_used_in_display')}{/tr}{/if}</li>
			{if !$serviceHash.stars_user_pixels and $gBitUser->isRegistered() && $gBitUser->mUserId != $serviceHash.user_id}
				{foreach from=$starsLinks item=k key=rate}
					<li><a href="javascript:ajax_updater( 'stars-{$serviceHash.content_id}', '{$smarty.const.STARS_PKG_URL}rate.php', 'content_id={$serviceHash.content_id}&amp;stars_rating={$rate}' );" title="{tr}Stars{/tr}: {$rate}" class="stars-{$rate}">{$rate}</a></li>
				{/foreach}
			{/if}
		</ul>

		<small>
			{if $serviceHash.stars_rating}
				{$current} / {$gBitSystem->getConfig('stars_used_in_display')} {tr}in {$serviceHash.stars_rating_count} votes{/tr}
			{elseif $serviceHash.stars_user_rating}
				{tr}Your rating:{/tr} {$serviceHash.stars_user_rating} / {$gBitSystem->getConfig('stars_used_in_display')}
			{else}
				{if $gBitUser->mUserId == $serviceHash.user_id}{tr}You can not rate your own content.{/tr}{else}{tr}Waiting for {$gBitSystem->getConfig('stars_minimum_ratings',5)} ratings{/tr}{/if}
			{/if}
			{if !$gBitUser->isRegistered()}
				<br />{tr}You need to <a href="{$smarty.const.USERS_PKG_URL}login.php">log in</a> to rate.{/tr}
			{/if}
		</small>

		{if $gBitSystem->isFeatureActive( "stars_rerating" )}
			<ul class="stars-rating">
				<li class="stars-current" style="width:{$serviceHash.stars_user_pixels|default:0}px;">{$current} / {$gBitSystem->getConfig('stars_used_in_display')} in {$serviceHash.stars_rating_count} {tr}votes{/tr}</li>
				{if $gBitUser->isRegistered() && $gBitUser->mUserId != $serviceHash.user_id}
					{foreach from=$starsLinks item=k key=rate}
						<li><a href="javascript:ajax_updater( 'stars-{$serviceHash.content_id}', '{$smarty.const.STARS_PKG_URL}rate.php', 'content_id={$serviceHash.content_id}&amp;stars_rating={$rate}' );" title="{tr}Stars{/tr}: {$rate}" class="stars-{$rate}">{$rate}</a></li>
					{/foreach}
				{/if}
			</ul>
		{/if}
	</div>
	{formfeedback hash=$starsfeed}
{/if}
{/strip}

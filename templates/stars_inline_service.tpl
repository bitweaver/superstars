{if $loadStars}
	<script type="text/javascript">/*<![CDATA[*/ show_spinner('spinner'); /*]]>*/</script>
	{if $serviceHash.stars_user_pixels}
		<div class="stars-container" id="stars-{$serviceHash.content_id}">
			<ul class="stars-rating">
				<li class="stars-current" style="width:{$serviceHash.stars_pixels|default:0}px;"></li>
			</ul>

			{if !$serviceHash.stars_rating}
				<small>{if $gBitUser->mUserId == $serviceHash.user_id}{tr}You cannot rate your own content.{/tr}{else}{tr}Your rating:{/tr} {$serviceHash.stars_user_rating} / {$gBitSystem->getConfig('stars_used_in_display')}{/if}{*<br />{tr}Waiting for {$gBitSystem->getConfig('stars_minimum_ratings',5)} ratings{/tr}*}</small><br />
			{else}
				<small>{if $serviceHash.stars_rating}{math equation="rating * stars / 100" stars=$gBitSystem->getConfig('stars_used_in_display') rating=`$serviceHash.stars_rating` format="%.1f"} / {$gBitSystem->getConfig('stars_used_in_display')} in {$serviceHash.stars_rating_count} {tr}votes{/tr}{/if}</small>
			{/if}

			{if $gBitSystem->isFeatureActive( "stars_rerating" )}
				<ul class="stars-rating">
					<li class="stars-current" style="width:{$serviceHash.stars_user_pixels|default:0}px;">{math equation="rating * stars / 100" stars=$gBitSystem->getConfig('stars_used_in_display') rating=`$serviceHash.stars_rating` format="%.1f"} / {$gBitSystem->getConfig('stars_used_in_display')} in {$serviceHash.stars_rating_count} {tr}votes{/tr}</li>
					{if $gBitUser->isRegistered() && $gBitUser->mUserId != $serviceHash.user_id}
						{foreach from=$starsLinks item=k key=rate}
							<li><a href="javascript:ajax_updater( 'stars-{$serviceHash.content_id}', '{$smarty.const.STARS_PKG_URL}rate.php', 'content_id={$serviceHash.content_id}&amp;stars_rating={$rate}' );" title="{tr}Stars{/tr}: {$rate}" class="stars-{$rate}">{$rate}</a></li>
						{/foreach}
					{/if}
				</ul>
			{/if}
		</div>
		{formfeedback hash=$starsfeed}
	{else}
		<div id="stars-{$serviceHash.content_id}">
			<ul class="stars-rating">
				<li class="stars-current" style="width:{$serviceHash.stars_pixels|default:0}px;">{if $serviceHash.stars_rating}{math equation="rating * stars / 100" stars=$gBitSystem->getConfig('stars_used_in_display') rating=`$serviceHash.stars_rating` format="%.1f"} / {$gBitSystem->getConfig('stars_used_in_display')} in {$serviceHash.stars_rating_count} {tr}votes{/tr}{/if}</li>
				{if $gBitUser->isRegistered() && $gBitUser->mUserId != $serviceHash.user_id}
					{foreach from=$starsLinks item=k key=rate}
						<li><a href="javascript:ajax_updater( 'stars-{$serviceHash.content_id}', '{$smarty.const.STARS_PKG_URL}rate.php', 'content_id={$serviceHash.content_id}&amp;stars_rating={$rate}' );" title="{tr}Stars{/tr}: {$rate}" class="stars-{$rate}">{$rate}</a></li>
					{/foreach}
				{/if}
			</ul>

			{if !$gBitUser->isRegistered()}
				<small>{tr}You need to <a href="{$smarty.const.USERS_PKG_URL}login.php">log in</a> to rate.{/tr}</small><br />
			{elseif $gBitUser->mUserId == $serviceHash.user_id}
				<small>{tr}You cannot rate your own content.{/tr}</small>
			{/if}
		</div>
		{formfeedback hash=$starsfeed}
	{/if}
{/if}

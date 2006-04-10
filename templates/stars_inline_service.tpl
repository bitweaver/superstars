{if $loadStars}
	{if $gBitUser->isRegistered() && $gBitUser->mUserId != $serviceHash.user_id}
		{capture name=starsLinks}
			{foreach from=$starsLinks item=k key=rate}
				<li><a href="javascript:ajax_updater( 'stars-{$serviceHash.content_id}', '{$smarty.const.STARS_PKG_URL}rate.php', 'content_id={$serviceHash.content_id}&amp;stars_rating={$rate}' );" title="{tr}Stars{/tr}: {$rate}" class="stars-{$rate}">{$rate}</a></li>
			{/foreach}
		{/capture}
	{/if}

	{capture name=starsRating}
		{if $serviceHash.stars_rating}
			{math equation="rating * stars / 100" stars=$gBitSystem->getConfig('stars_used_in_display') rating=`$serviceHash.stars_rating` format="%.1f"} / {$gBitSystem->getConfig('stars_used_in_display')} in {$serviceHash.stars_rating_count} {tr}votes{/tr}
		{/if}
	{/capture}

	{if $serviceHash.stars_user_pixels}
		<div class="stars-container" id="stars-{$serviceHash.content_id}">
			<ul class="stars-rating">
				<li class="stars-current" style="width:{$serviceHash.stars_pixels|default:0}px;"></li>
			</ul>

			{if !$serviceHash.stars_rating}
				<small>{if $gBitUser->mUserId == $serviceHash.user_id}{tr}You cannot rate your own content.{/tr}{else}{tr}Waiting for {$gBitSystem->getConfig('stars_minimum_ratings',5)} ratings{/tr}{/if}</small><br />
			{else}
				<small>{$smarty.capture.starsRating}</small>
			{/if}

			{if $gBitSystem->isFeatureActive( "stars_rerating" )}
				<ul class="stars-rating">
					<li class="stars-current" style="width:{$serviceHash.stars_user_pixels|default:0}px;">{math equation="rating * stars / 100" stars=$gBitSystem->getConfig('stars_used_in_display') rating=`$serviceHash.stars_rating` format="%.1f"} / {$gBitSystem->getConfig('stars_used_in_display')} in {$serviceHash.stars_rating_count} {tr}votes{/tr}</li>
					{$smarty.capture.starsLinks}
				</ul>
			{/if}
		</div>
		{formfeedback hash=$starsfeed}
	{else}
		<div id="stars-{$serviceHash.content_id}">
			<ul class="stars-rating">
				<li class="stars-current" style="width:{$serviceHash.stars_pixels|default:0}px;">{$smarty.capture.starsRating}</li>
				{$smarty.capture.starsLinks}
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

{strip}
{capture name=stars_links}
	{if $gBitUser->isRegistered() && $gBitUser->mUserId != $serviceHash.user_id}
		<li><a href="javascript:ajax_updater( 'stars-{$serviceHash.content_id}', '{$smarty.const.STARS_PKG_URL}rate.php', 'content_id={$serviceHash.content_id}&amp;stars_rating={counter name=stars start=1}' );" title="{tr}Stars{/tr}: 1" class="stars-1">1</a></li>
		<li><a href="javascript:ajax_updater( 'stars-{$serviceHash.content_id}', '{$smarty.const.STARS_PKG_URL}rate.php', 'content_id={$serviceHash.content_id}&amp;stars_rating={counter name=stars}' );" title="{tr}Stars{/tr}: 2" class="stars-2">2</a></li>
		<li><a href="javascript:ajax_updater( 'stars-{$serviceHash.content_id}', '{$smarty.const.STARS_PKG_URL}rate.php', 'content_id={$serviceHash.content_id}&amp;stars_rating={counter name=stars}' );" title="{tr}Stars{/tr}: 3" class="stars-3">3</a></li>
		<li><a href="javascript:ajax_updater( 'stars-{$serviceHash.content_id}', '{$smarty.const.STARS_PKG_URL}rate.php', 'content_id={$serviceHash.content_id}&amp;stars_rating={counter name=stars}' );" title="{tr}Stars{/tr}: 4" class="stars-4">4</a></li>
		<li><a href="javascript:ajax_updater( 'stars-{$serviceHash.content_id}', '{$smarty.const.STARS_PKG_URL}rate.php', 'content_id={$serviceHash.content_id}&amp;stars_rating={counter name=stars}' );" title="{tr}Stars{/tr}: 5" class="stars-5">5</a></li>
	{/if}
{/capture}

{if $serviceHash.stars_user_pixels}
	<div id="stars-{$serviceHash.content_id}">
		{tr}Average Rating{/tr}
		<ul class="stars-rating">
			<li class="stars-current" style="width:{$serviceHash.stars_pixels}px;">{tr}Rating{/tr}: {$serviceHash.stars_rating}</li>
		</ul>
		{if !$gBitUser->isRegistered()}
			<small>{tr}You need to log in to rate.{/tr}</small><br />
		{elseif !$serviceHash.stars_rating}
			<small>{tr}waiting for {$gBitSystemPrefs.stars_minimum_ratings|default:5} ratings{/tr}</small><br />
		{/if}

		{tr}Your Rating{/tr}
		<ul class="stars-rating">
			<li class="stars-current" style="width:{$serviceHash.stars_user_pixels}px;">{tr}Rating{/tr}: {$serviceHash.stars_rating}</li>
			{$smarty.capture.stars_links}
		</ul>
	</div>
	{formfeedback hash=$starsfeed}
{else}
	<div id="stars-{$serviceHash.content_id}">
		{tr}Average Rating{/tr}
		<ul class="stars-rating">
			<li class="stars-current" style="width:{$serviceHash.stars_pixels}px;">{tr}Rating{/tr}: {$serviceHash.stars_rating}</li>
			{$smarty.capture.stars_links}
		</ul>
		{if !$gBitUser->isRegistered()}
			<small>{tr}You need to log in to rate.{/tr}</small><br />
		{elseif !$serviceHash.stars_rating}
			<small>{tr}waiting for {$gBitSystemPrefs.stars_minimum_ratings|default:5} ratings{/tr}</small><br />
		{/if}
	</div>
	{formfeedback hash=$starsfeed}
{/if}
{/strip}

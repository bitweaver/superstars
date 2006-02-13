{strip}
<div id="stars">
	<ul class="stars-rating">
		<li class="stars-current" style="width:{$serviceHash.stars_pixels}px;">{tr}Rating{/tr}: {$serviceHash.stars_rating}</li>
		{if $serviceHash.user_id == $gBitUser->mUserId}
		{elseif $gBitUser->isRegistered()}
			<li><a href="javascript:ajax_updater( 'stars', '{$smarty.const.STARS_PKG_URL}rate.php', 'content_id={$serviceHash.content_id}&amp;stars_rating={counter}' );" title="{tr}Stars{/tr}: 1" class="stars-1">1</a></li>
			<li><a href="javascript:ajax_updater( 'stars', '{$smarty.const.STARS_PKG_URL}rate.php', 'content_id={$serviceHash.content_id}&amp;stars_rating={counter}' );" title="{tr}Stars{/tr}: 2" class="stars-2">2</a></li>
			<li><a href="javascript:ajax_updater( 'stars', '{$smarty.const.STARS_PKG_URL}rate.php', 'content_id={$serviceHash.content_id}&amp;stars_rating={counter}' );" title="{tr}Stars{/tr}: 3" class="stars-3">3</a></li>
			<li><a href="javascript:ajax_updater( 'stars', '{$smarty.const.STARS_PKG_URL}rate.php', 'content_id={$serviceHash.content_id}&amp;stars_rating={counter}' );" title="{tr}Stars{/tr}: 4" class="stars-4">4</a></li>
			<li><a href="javascript:ajax_updater( 'stars', '{$smarty.const.STARS_PKG_URL}rate.php', 'content_id={$serviceHash.content_id}&amp;stars_rating={counter}' );" title="{tr}Stars{/tr}: 5" class="stars-5">5</a></li>
		{/if}
	</ul>
	{if !$serviceHash.stars_rating}
		<small>{tr}waiting for {$gBitSystemPrefs.stars_minimum_ratings|default:5} ratings{/tr}</small>
	{/if}
</div>
{formfeedback hash=$starsfeed}
{/strip}

{strip}
{if $user_stars.stars_load}
	{if $gBitSystem->isFeatureActive( 'stars_use_ajax' )}
		<script type="text/javascript">/*<![CDATA[*/ show_spinner('spinner'); /*]]>*/</script>
	{/if}
	<div class="stars-container" id="stars-{$user_stars.content_id}">
			<small>{tr}Contributions{/tr}</small>
			<ul class="stars-rating" >
				<li class="stars-current" style="width:{$user_stars.stars_pixels|default:0}px;"></li>
			</ul>
			<small>
				{if $user_stars.stars_rating}
					{math equation="rating * stars / 100" stars=$gBitSystem->getConfig('stars_used_in_display') rating=$user_stars.stars_rating format="%.1f"} / {$gBitSystem->getConfig('stars_used_in_display')} {tr}in {$user_stars.stars_rating_count} votes{/tr}
				{else}
					{tr}Waiting for {$gBitSystem->getConfig('stars_minimum_ratings',5)} ratings{/tr}
				{/if}
			</small>
	</div>
{/if}
{/strip}
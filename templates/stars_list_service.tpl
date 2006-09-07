{strip}
{if $loadStars}
	{if $serviceHash.stars_pixels}
		<div id="stars-rating-{$serviceHash.stars_load}" class="stars-rating"><div class="stars-current" style="width:{$serviceHash.stars_pixels}px;"></div></div>
		{if $gBitSystem->isFeatureActive( 'stars_per_version_rating' )}
			<div id="stars-version-rating-{$serviceHash.stars_load}" class="stars-rating"><div class="stars-current" style="width:{$serviceHash.stars_version_pixels}px;"></div></div>
		{/if}
	{/if}
{/if}
{/strip}

{strip}
{if $loadStars}
	{if $serviceHash.stars_pixels}
		<div class="stars-rating"><div class="stars-current" style="width:{$serviceHash.stars_pixels}px;"></div></div>
		{if $gBitSystem->isFeatureActive( 'stars_per_version_rating' )}
			<div class="stars-rating"><div class="stars-current" style="width:{$serviceHash.stars_version_pixels}px;"></div></div>
		{/if}
	{/if}
{/if}
{/strip}

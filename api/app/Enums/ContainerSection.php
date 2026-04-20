<?php

	namespace App\Enums;
	enum ContainerSection: string
	{
		case BANNER = 'BannerContainer';
		case SLIDER = 'SliderContainer';
		case COLUMN = 'ColumnContainer';
		case GAMES_CATEGORY = 'GamesCategoryContainer';
		case FLEX_COLUMN = 'FlexColumnContainer';
		case CAROUSEL = 'CarouselContainer';
		case HTML = 'HTMLContainer';
		case BET_FEED = 'BetFeedContainer';
		case SEO = 'SeoContainer';
		case LOGOS = 'LogosContainer';
		case SEARCH = 'CasinoSearchContainer';
		case TABS = 'TabContainer';
		case PROVIDER_LOGOS = 'ProviderLogosContainer';
		case CATEGORY_HEADLESS = 'GamesCategoryHeadlessContainer';
		case ACCORDION = 'AccordionContainer';
		case CATEGORY_DETAILS = 'GamesCategoryDetailsContainer';
		case PROMOTIONS = 'PromotionsContainer';
		case PROMO_IMAGE = 'PromotionImageContainer';
		case PROMO_TITLE = 'PromotionTitleContainer';
		case PROMO_SUBTITLE = 'PromotionSubTitleContainer';
		case PROMO_DESCRIPTION = 'PromotionDescriptionContainer';
		case PROMO_BUTTON = 'PromotionButtonContainer';
		case BONUS = 'HomepageBonusContainer';
		case OFFER = 'HomepageOfferContainer';
		case PLAYER_FAVORITES = 'PlayerFavouritesContainer';
	}
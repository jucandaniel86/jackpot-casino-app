import type { GameType } from './Game'

export enum HeaderContainerE {
  LIST = 'GamesCategoryHeadlessContainer',
  CAROUSEL = 'GamesCategoryContainer',
}

export type TabsFetchInitialState = {
  page: number
  size: number
  totalItems: number
  data: GameType[]
}

export type ResolutionConfigType = {
  aspectRatioPercentage: number
  headingFontFamily: string
  headingFontSize: number
  headingFontWeight: string
  itemsPerRow: number
  viewMoreFontFamily: string
  viewMoreFontSize: number
  viewMoreFontWeight: string
}

export type ResolutionValuesType = {
  LG: ResolutionConfigType
  MD: ResolutionConfigType
  SM: ResolutionConfigType
  XL: ResolutionConfigType
  XS: ResolutionConfigType
}

export type TabsFetchData = {
  aspectRatioPercentage: number
  fetchUrl: string
  iconParams: unknown
  id: string
  slug: string
  title: string
  viewMoreConfig: unknown
  resolutionsConfig: ResolutionValuesType
  initialState: TabsFetchInitialState
}

export type TabsFetchType = {
  id: string
  container: HeaderContainerE
  children: string[]
  data: TabsFetchData
}

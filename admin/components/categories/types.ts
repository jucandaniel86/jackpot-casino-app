export enum CategoryViewType {
  EDITABLE = "editable",
  SELECTABLE = "selectable",
}

export type CategoryType = {
  id: number;
  parent_id: number;
  name: string;
  seo: any;
  slug: string;
  restricted: boolean;
  descendants: CategoryType[];
};

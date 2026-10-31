import { model, Schema, Types, type InferSchemaType } from 'mongoose';

const productSchema = new Schema({
  legacyId: { type: Number, required: true, min: 1, unique: true },
  categoryRef: { type: Types.ObjectId, ref: 'Category', required: true },
  categorySlug: { type: String, required: true, maxlength: 100 },
  name: { type: String, required: true, maxlength: 160 },
  slug: { type: String, required: true, maxlength: 180, unique: true },
  description: { type: String, required: true },
  priceCents: { type: Number, required: true, min: 0, validate: Number.isSafeInteger },
  originalPriceCents: { type: Number, default: null, min: 0, validate: (value: number | null) => value === null || Number.isSafeInteger(value) },
  rating: { type: Number, required: true, min: 0, max: 5 },
  reviewCount: { type: Number, required: true, min: 0, validate: Number.isSafeInteger },
  images: { type: [String], required: true, validate: (value: string[]) => value.length >= 1 && value.length <= 4 },
  colour: { type: String, required: true, maxlength: 50 },
  isNew: { type: Boolean, required: true, default: false },
  isTopSelling: { type: Boolean, required: true, default: false },
  legacyCreatedAt: { type: Date, required: true },
}, { timestamps: true, suppressReservedKeysWarning: true });

productSchema.index({ categorySlug: 1, priceCents: 1, legacyId: 1 });
productSchema.index({ colour: 1, priceCents: 1, legacyId: 1 });
productSchema.index({ legacyCreatedAt: -1, legacyId: -1 });
productSchema.index({ isTopSelling: -1, isNew: -1, legacyId: 1 });
productSchema.index({ priceCents: 1, legacyId: 1 });
productSchema.index({ priceCents: -1, legacyId: -1 });

export type ProductRecord = InferSchemaType<typeof productSchema>;
export type PopulatedProduct = Omit<ProductRecord, 'categoryRef'> & { categoryRef: { name: string; slug: string } };
export const Product = model('Product', productSchema);

import { model, Schema, type InferSchemaType } from 'mongoose';

const categorySchema = new Schema({
  legacyId: { type: Number, required: true, min: 1, unique: true },
  name: { type: String, required: true, maxlength: 100 },
  slug: { type: String, required: true, maxlength: 100, unique: true, lowercase: true },
}, { timestamps: true });

export type CategoryRecord = InferSchemaType<typeof categorySchema>;
export const Category = model('Category', categorySchema);

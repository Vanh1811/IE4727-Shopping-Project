import { model, Schema, Types, type InferSchemaType } from 'mongoose';

const reviewSchema = new Schema({
  decision: { type: String, enum: ['approve', 'request_changes'], required: true },
  changeRequest: { type: String, maxlength: 500, default: '' },
  revision: { type: Number, required: true, min: 1 },
  createdAt: { type: Date, required: true, default: Date.now },
}, { _id: true });

const mockupSchema = new Schema({
  orderNumber: { type: Number, required: true, min: 1, index: true },
  orderItemId: { type: Types.ObjectId, required: true, unique: true },
  previewImage: { type: String, required: true, maxlength: 255 },
  printPosition: { type: String, required: true, maxlength: 100 },
  printColour: { type: String, required: true, maxlength: 100 },
  customText: { type: String, maxlength: 500, default: '' },
  status: { type: String, enum: ['READY_FOR_REVIEW', 'CHANGES_REQUESTED', 'APPROVED'], required: true, default: 'READY_FOR_REVIEW' },
  revision: { type: Number, required: true, min: 1, default: 1 },
  reviews: { type: [reviewSchema], default: [] },
}, { timestamps: true });

mockupSchema.index({ orderNumber: 1, status: 1 });
export type MockupRecord = InferSchemaType<typeof mockupSchema>;
export const Mockup = model('Mockup', mockupSchema);

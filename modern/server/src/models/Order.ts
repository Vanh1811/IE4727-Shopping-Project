import { model, Schema, type InferSchemaType } from 'mongoose';

const orderItemSchema = new Schema({
  productId: { type: Number, required: true, min: 1 },
  productSlug: { type: String, required: true },
  productName: { type: String, required: true, maxlength: 160 },
  image: { type: String, required: true },
  size: { type: String, required: true, enum: ['S', 'M', 'L', 'XL', 'ONE SIZE'] },
  colour: { type: String, required: true, maxlength: 50 },
  unitPriceCents: { type: Number, required: true, min: 0, validate: Number.isSafeInteger },
  quantity: { type: Number, required: true, min: 1, max: 99, validate: Number.isSafeInteger },
  lineTotalCents: { type: Number, required: true, min: 0, validate: Number.isSafeInteger },
}, { _id: true });

const orderSchema = new Schema({
  orderNumber: { type: Number, required: true, unique: true, min: 1 },
  fullName: { type: String, required: true, maxlength: 160 },
  email: { type: String, required: true, maxlength: 254 },
  phone: { type: String, required: true, maxlength: 30 },
  postalCode: { type: String, required: true, maxlength: 20 },
  deliveryAddress: { type: String, required: true, maxlength: 500 },
  specialInstructions: { type: String, maxlength: 1000, default: '' },
  items: { type: [orderItemSchema], required: true, validate: (items: unknown[]) => items.length > 0 },
  subtotalCents: { type: Number, required: true, min: 0, validate: Number.isSafeInteger },
  discountCents: { type: Number, required: true, min: 0, validate: Number.isSafeInteger },
  deliveryCents: { type: Number, required: true, min: 0, validate: Number.isSafeInteger },
  totalCents: { type: Number, required: true, min: 0, validate: Number.isSafeInteger },
  status: { type: String, required: true, enum: ['ORDER_PLACED', 'DESIGN_REVIEW', 'CHANGES_REQUESTED', 'CONFIRMED', 'PROCESSING', 'COMPLETED'], default: 'ORDER_PLACED' },
  accessCodeHash: { type: String, required: true, minlength: 64, maxlength: 64, unique: true },
  idempotencyKey: { type: String, required: true, maxlength: 80, unique: true },
  emailStatus: { type: String, enum: ['disabled', 'failed', 'sent'], default: 'disabled' },
}, { timestamps: true });

orderSchema.index({ status: 1, createdAt: -1 });

export type OrderRecord = InferSchemaType<typeof orderSchema>;
export const Order = model('Order', orderSchema);

import { model, Schema } from 'mongoose';

const counterSchema = new Schema({ _id: { type: String, required: true }, sequence: { type: Number, required: true, min: 0 } }, { versionKey: false });
export const Counter = model('Counter', counterSchema);

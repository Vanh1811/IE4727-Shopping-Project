ALTER TABLE order_items ADD UNIQUE KEY uq_order_items_order_id_id (order_id, id);
ALTER TABLE mockups ADD CONSTRAINT fk_mockups_order_item_owner
  FOREIGN KEY (order_id, order_item_id) REFERENCES order_items (order_id, id)
  ON UPDATE CASCADE ON DELETE CASCADE;

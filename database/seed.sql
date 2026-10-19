USE f2b_shop;

INSERT INTO categories (id, name, slug) VALUES
    (1, 'T-Shirts', 't-shirts'),
    (2, 'Shirts', 'shirts'),
    (3, 'Hoodies', 'hoodies'),
    (4, 'Jackets', 'jackets'),
    (5, 'Pants', 'pants'),
    (6, 'Accessories', 'accessories')
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    slug = VALUES(slug);

INSERT INTO products (
    id,
    category_id,
    name,
    slug,
    description,
    price,
    original_price,
    rating,
    review_count,
    image,
    image_2,
    image_3,
    image_4,
    colour,
    is_new,
    is_top_selling,
    created_at
) VALUES
    (1, 1, 'Classic Black T-Shirt', 'classic-black-t-shirt', 'A heavyweight cotton tee with a clean shape for everyday rotation.', 29.90, NULL, 4.8, 124, 'images/products/form-tee.svg', NULL, NULL, NULL, 'Black', 1, 1, '2026-10-01 09:00:00'),
    (2, 1, 'Oversized Graphic Tee', 'oversized-graphic-tee', 'A relaxed graphic tee with a soft handle and an easy oversized fit.', 34.90, 39.90, 4.7, 86, 'images/products/contour-rib-tee.svg', NULL, NULL, NULL, 'White', 1, 0, '2026-09-28 09:00:00'),
    (3, 1, 'Contour Rib Tee', 'contour-rib-tee', 'A softly structured rib tee that works on its own or under an open layer.', 36.00, NULL, 4.9, 98, 'images/products/contour-rib-tee.svg', NULL, NULL, NULL, 'Coral', 1, 1, '2026-09-24 09:00:00'),
    (4, 1, 'Everyday Cotton Tee', 'everyday-cotton-tee', 'A dependable cotton jersey tee with a considered neckline and relaxed fit.', 31.90, NULL, 4.6, 61, 'images/products/form-tee.svg', NULL, NULL, NULL, 'Navy', 0, 0, '2026-08-14 09:00:00'),
    (5, 2, 'Open Line Shirt', 'open-line-shirt', 'An easy-fit shirt with a clean line for dressing up or down.', 58.00, NULL, 4.8, 77, 'images/products/open-line-shirt.svg', NULL, NULL, NULL, 'Cream', 1, 1, '2026-09-20 09:00:00'),
    (6, 2, 'Relaxed Fit Shirt', 'relaxed-fit-shirt', 'A versatile shirt with a soft drape and room to move through the day.', 49.90, NULL, 4.6, 73, 'images/products/open-line-shirt.svg', NULL, NULL, NULL, 'Blue', 0, 0, '2026-08-22 09:00:00'),
    (7, 2, 'Studio Overshirt', 'studio-overshirt', 'A structured cotton-blend overshirt designed for everyday layering.', 68.00, 82.00, 4.8, 24, 'images/products/studio-overshirt.svg', 'images/products/studio-overshirt-detail.svg', 'images/products/studio-overshirt-back.svg', 'images/products/studio-overshirt-fit.svg', 'Black', 1, 1, '2026-09-16 09:00:00'),
    (8, 2, 'Minimalist Overshirt', 'minimalist-overshirt', 'A pared-back overshirt with a relaxed shape and understated finish.', 68.90, NULL, 4.9, 118, 'images/products/open-line-shirt.svg', NULL, NULL, NULL, 'Olive', 0, 1, '2026-08-30 09:00:00'),
    (9, 3, 'Essential Hoodie', 'essential-hoodie', 'A comfortable everyday hoodie with a clean front and generous fit.', 59.90, NULL, 4.9, 212, 'images/products/studio-overshirt.svg', NULL, NULL, NULL, 'Ink', 0, 1, '2026-08-26 09:00:00'),
    (10, 3, 'Everyday Sweatshirt', 'everyday-sweatshirt', 'A soft sweatshirt with an easy silhouette for cooler days.', 54.90, NULL, 4.8, 91, 'images/products/contour-rib-tee.svg', NULL, NULL, NULL, 'Mist', 0, 0, '2026-08-10 09:00:00'),
    (11, 4, 'Lightweight Jacket', 'lightweight-jacket', 'A lightweight layer with a straightforward shape and practical pockets.', 84.90, NULL, 4.7, 64, 'images/products/studio-overshirt.svg', NULL, NULL, NULL, 'Sand', 1, 0, '2026-09-12 09:00:00'),
    (12, 4, 'Canvas Jacket', 'canvas-jacket', 'A durable canvas layer made for transitional weather and daily wear.', 89.90, NULL, 4.8, 42, 'images/products/soft-frame-tote.svg', NULL, NULL, NULL, 'Olive', 0, 0, '2026-07-28 09:00:00'),
    (13, 5, 'Straight-Leg Jeans', 'straight-leg-jeans', 'Straight-leg denim with a comfortable rise and an everyday wash.', 79.90, 94.90, 4.8, 158, 'images/products/arc-denim.svg', NULL, NULL, NULL, 'Blue', 0, 1, '2026-08-18 09:00:00'),
    (14, 5, 'Utility Cargo Pants', 'utility-cargo-pants', 'Relaxed cargo trousers with useful pockets and a clean tapered finish.', 69.90, NULL, 4.7, 67, 'images/products/everyday-pleat-trouser.svg', NULL, NULL, NULL, 'Navy', 0, 0, '2026-07-20 09:00:00'),
    (15, 6, 'Transit Crossbody', 'transit-crossbody', 'A compact crossbody designed to carry the essentials without the bulk.', 52.00, NULL, 4.8, 55, 'images/products/transit-crossbody.svg', NULL, NULL, NULL, 'Slate', 1, 0, '2026-09-08 09:00:00'),
    (16, 6, 'Soft Frame Tote', 'soft-frame-tote', 'A flexible everyday tote with enough room for the things you carry most.', 46.00, NULL, 4.7, 83, 'images/products/soft-frame-tote.svg', NULL, NULL, NULL, 'Olive', 0, 1, '2026-07-14 09:00:00')
ON DUPLICATE KEY UPDATE
    category_id = VALUES(category_id),
    name = VALUES(name),
    slug = VALUES(slug),
    description = VALUES(description),
    price = VALUES(price),
    original_price = VALUES(original_price),
    rating = VALUES(rating),
    review_count = VALUES(review_count),
    image = VALUES(image),
    image_2 = VALUES(image_2),
    image_3 = VALUES(image_3),
    image_4 = VALUES(image_4),
    colour = VALUES(colour),
    is_new = VALUES(is_new),
    is_top_selling = VALUES(is_top_selling),
    created_at = VALUES(created_at);

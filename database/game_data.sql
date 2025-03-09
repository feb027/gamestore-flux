-- Truncate the products table to start fresh
TRUNCATE TABLE products;

-- Insert popular games
INSERT INTO products (title, description, price, image_url, genre, developer, publisher, release_date, stock_status) VALUES
-- Action/Adventure Games
('Red Dead Redemption 2', 'Winner of over 175 Game of the Year Awards and recipient of over 250 perfect scores, Red Dead Redemption 2 is an epic tale of honor and loyalty at the dawn of the modern age. Experience the story of Arthur Morgan and the Van der Linde gang, outlaws on the run across America at the dawn of the modern age.', 939000, '/images/games/rdr2.jpg', 'Action', 'Rockstar Games', 'Rockstar Games', '2018-10-26', 'in_stock'),

('God of War Ragnarök', 'Embark on an epic and heartfelt journey as Kratos and Atreus struggle with holding on and letting go. Against a backdrop of Norse Realms torn asunder by the fury of Ragnarök, they must choose between their own safety and the safety of the realms.', 1099000, '/images/games/god-of-war.jpg', 'Action', 'Santa Monica Studio', 'Sony Interactive Entertainment', '2022-11-09', 'in_stock'),

('Elden Ring', 'The Golden Order has been broken. Rise, Tarnished, and be guided by grace to brandish the power of the Elden Ring and become an Elden Lord in the Lands Between.', 939000, '/images/games/elden-ring.jpg', 'RPG', 'FromSoftware', 'Bandai Namco', '2022-02-25', 'in_stock'),

-- RPG Games
('Baldurs Gate 3', 'Gather your party and return to the Forgotten Realms in a tale of fellowship and betrayal, sacrifice and survival, and the lure of absolute power. Mysterious abilities are awakening inside you, drawn from a Mind Flayer parasite planted in your brain.', 939000, '/images/games/baldurs-gate-3.jpg', 'RPG', 'Larian Studios', 'Larian Studios', '2023-08-03', 'in_stock'),

('Cyberpunk 2077', 'Cyberpunk 2077 is an open-world, action-adventure RPG set in Night City, a megalopolis obsessed with power, glamour and body modification.', 785000, '/images/games/cyberpunk-2077.jpg', 'RPG', 'CD Projekt Red', 'CD Projekt', '2020-12-10', 'in_stock'),

-- Sports Games
('EA Sports FC 24', 'Experience the next evolution of The World's Game with EA SPORTS FC™ 24, powered by football and over 19,000 fully licensed players, 700+ teams, and 30+ leagues.', 1099000, '/images/games/ea-fc-24.jpg', 'Sports', 'EA Sports', 'Electronic Arts', '2023-09-29', 'in_stock'),

('NBA 2K24', 'Rise to the moment in NBA 2K24. Showcase your talent in MyCAREER. Build your perfect lineup in MyTEAM. Experience the history of the game with the NBA's greatest players.', 939000, '/images/games/nba-2k24.jpg', 'Sports', 'Visual Concepts', '2K Sports', '2023-09-08', 'in_stock'),

-- Racing Games
('Forza Horizon 5', 'Your Ultimate Horizon Adventure awaits! Explore the vibrant and ever-evolving open world landscapes of Mexico with limitless, fun driving action in hundreds of the world's greatest cars.', 939000, '/images/games/forza-horizon-5.jpg', 'Racing', 'Playground Games', 'Xbox Game Studios', '2021-11-09', 'in_stock'),

('Gran Turismo 7', 'Whether you're a competitive or casual racer, collector, tuner, livery designer or photographer – find your line with a staggering collection of game modes including fan-favorites like GT Campaign, Arcade and Driving School.', 1099000, '/images/games/gt7.jpg', 'Racing', 'Polyphony Digital', 'Sony Interactive Entertainment', '2022-03-04', 'in_stock'),

-- Strategy Games
('Civilization VI', 'Create a civilization to stand the test of time! Explore new land, research technology, conquer your enemies, and go head-to-head with historical leaders as you attempt to build the greatest civilization the world has ever known.', 469000, '/images/games/civilization-6.jpg', 'Strategy', 'Firaxis Games', '2K Games', '2016-10-21', 'in_stock'),

('Age of Empires IV', 'One of the most beloved real-time strategy games returns to glory with Age of Empires IV, putting you at the center of epic historical battles that shaped the world.', 629000, '/images/games/age-of-empires-4.jpg', 'Strategy', 'Relic Entertainment', 'Xbox Game Studios', '2021-10-28', 'in_stock'),

-- Adventure Games
('The Legend of Zelda: Tears of the Kingdom', 'An epic adventure across the land and skies of Hyrule awaits. In this sequel to The Legend of Zelda: Breath of the Wild, you'll decide your own path through the sprawling landscapes of Hyrule and the mysterious islands floating in the vast skies above.', 1099000, '/images/games/zelda-totk.jpg', 'Adventure', 'Nintendo', 'Nintendo', '2023-05-12', 'in_stock'),

('Horizon Forbidden West', 'Join Aloy as she braves the Forbidden West – a majestic but dangerous frontier that conceals mysterious new threats.', 785000, '/images/games/horizon-fw.jpg', 'Adventure', 'Guerrilla Games', 'Sony Interactive Entertainment', '2022-02-18', 'in_stock'),

-- Recently Released Games
('Starfield', 'Starfield is the first new universe in 25 years from Bethesda Game Studios, the award-winning creators of The Elder Scrolls V: Skyrim and Fallout 4.', 1099000, '/images/games/starfield.jpg', 'RPG', 'Bethesda Game Studios', 'Bethesda Softworks', '2023-09-06', 'in_stock'),

('Marvel's Spider-Man 2', 'Spider-Men Peter Parker and Miles Morales return for an exciting new adventure in the critically acclaimed Marvel's Spider-Man franchise for PS5.', 1099000, '/images/games/spiderman-2.jpg', 'Action', 'Insomniac Games', 'Sony Interactive Entertainment', '2023-10-20', 'in_stock'); 
-- BlueDesk Google News support
-- Run once against the production database before uploading the code package.
ALTER TABLE blog_posts
  ADD COLUMN is_news TINYINT(1) NOT NULL DEFAULT 0 AFTER status,
  ADD INDEX idx_blog_news_published (is_news, status, published_at);

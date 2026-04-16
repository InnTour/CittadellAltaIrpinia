CREATE TABLE IF NOT EXISTS points_of_interest (
  id            VARCHAR(100) NOT NULL,
  borough_id    VARCHAR(100) NOT NULL,
  category      VARCHAR(60)  DEFAULT NULL,
  sort_order    INT          DEFAULT 0,

  name_it       VARCHAR(200) NOT NULL,
  name_en       VARCHAR(200) DEFAULT NULL,
  name_irp      VARCHAR(200) DEFAULT NULL,

  desc_it       TEXT         DEFAULT NULL,
  desc_en       TEXT         DEFAULT NULL,
  desc_irp      TEXT         DEFAULT NULL,

  tags          TEXT         DEFAULT NULL,

  cover_image   VARCHAR(500) DEFAULT NULL,
  images        TEXT         DEFAULT NULL,

  audio_it      VARCHAR(500) DEFAULT NULL,
  audio_en      VARCHAR(500) DEFAULT NULL,
  audio_irp     VARCHAR(500) DEFAULT NULL,
  transcript_it TEXT         DEFAULT NULL,
  transcript_en TEXT         DEFAULT NULL,
  transcript_irp TEXT        DEFAULT NULL,

  video_it      VARCHAR(500) DEFAULT NULL,
  video_en      VARCHAR(500) DEFAULT NULL,

  created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  FOREIGN KEY (borough_id) REFERENCES boroughs(id) ON DELETE CASCADE,
  KEY idx_borough_id (borough_id),
  KEY idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

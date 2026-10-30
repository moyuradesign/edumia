<?php

namespace App\Helpers;

final class Common_helper
{
    public const LANGUAGES = [
        'en' => 'English', 'zh' => '中文', 'fr' => 'Français', 'de' => 'Deutsch',
        'ja' => '日本語', 'km' => 'ខ្មែរ', 'ko' => '한국어', 'vi' => 'Tiếng Việt',
    ];

    private const LABELS = [
        'en' => ['headline'=>'A better way to learn, teach and grow.','intro'=>'Discover practical learning experiences, structured lessons and a supportive space for every learner.','courses'=>'Explore courses','search'=>'Search courses','featured'=>'Featured learning paths','view'=>'View course','back'=>'Back to courses','topic'=>'What you will learn','empty'=>'No courses match your search.','clear'=>'Clear search','preview'=>'Preview catalogue','coming'=>'Enrollment will be available in a later milestone.'],
        'zh' => ['headline'=>'更好的学习、教学与成长方式。','intro'=>'探索实用的学习体验、系统课程与支持每位学习者的空间。','courses'=>'探索课程','search'=>'搜索课程','featured'=>'精选学习课程','view'=>'查看课程','back'=>'返回课程','topic'=>'您将学到','empty'=>'没有找到相关课程。','clear'=>'清除搜索','preview'=>'课程预览','coming'=>'报名功能将在后续版本提供。'],
        'fr' => ['headline'=>'Une meilleure façon d’apprendre, d’enseigner et de progresser.','intro'=>'Découvrez des parcours pratiques et des leçons structurées.','courses'=>'Explorer les cours','search'=>'Rechercher','featured'=>'Parcours à découvrir','view'=>'Voir le cours','back'=>'Retour aux cours','topic'=>'Ce que vous apprendrez','empty'=>'Aucun cours trouvé.','clear'=>'Effacer','preview'=>'Aperçu des cours','coming'=>'Les inscriptions seront disponibles ultérieurement.'],
        'de' => ['headline'=>'Besser lernen, lehren und wachsen.','intro'=>'Entdecken Sie praxisnahe Lernangebote und strukturierte Lektionen.','courses'=>'Kurse entdecken','search'=>'Kurse suchen','featured'=>'Empfohlene Lernwege','view'=>'Kurs ansehen','back'=>'Zurück zu den Kursen','topic'=>'Das lernen Sie','empty'=>'Keine passenden Kurse gefunden.','clear'=>'Suche löschen','preview'=>'Kursvorschau','coming'=>'Anmeldungen folgen in einer späteren Version.'],
        'ja' => ['headline'=>'学び、教え、成長するための新しい方法。','intro'=>'実践的な学習体験と体系的なレッスンを見つけましょう。','courses'=>'コースを探す','search'=>'コースを検索','featured'=>'おすすめの学習コース','view'=>'コースを見る','back'=>'コースに戻る','topic'=>'学べる内容','empty'=>'コースが見つかりません。','clear'=>'検索をクリア','preview'=>'コースプレビュー','coming'=>'受講登録は今後の更新で利用可能になります。'],
        'km' => ['headline'=>'វិធីប្រសើរជាងមុនក្នុងការរៀន បង្រៀន និងរីកចម្រើន។','intro'=>'ស្វែងយល់ពីបទពិសោធន៍សិក្សាជាក់ស្តែង និងមេរៀនដែលមានរចនាសម្ព័ន្ធ។','courses'=>'ស្វែងរកវគ្គសិក្សា','search'=>'ស្វែងរកវគ្គសិក្សា','featured'=>'វគ្គសិក្សាពិសេស','view'=>'មើលវគ្គសិក្សា','back'=>'ត្រឡប់ទៅវគ្គសិក្សា','topic'=>'អ្វីដែលអ្នកនឹងរៀន','empty'=>'រកមិនឃើញវគ្គសិក្សាទេ។','clear'=>'សម្អាតការស្វែងរក','preview'=>'ការមើលវគ្គសិក្សាជាមុន','coming'=>'ការចុះឈ្មោះនឹងមាននៅដំណាក់កាលបន្ទាប់។'],
        'ko' => ['headline'=>'배우고 가르치며 성장하는 더 좋은 방법.','intro'=>'체계적인 수업과 실용적인 학습 경험을 만나보세요.','courses'=>'강좌 둘러보기','search'=>'강좌 검색','featured'=>'추천 학습 과정','view'=>'강좌 보기','back'=>'강좌로 돌아가기','topic'=>'배울 내용','empty'=>'검색 결과가 없습니다.','clear'=>'검색 초기화','preview'=>'강좌 미리보기','coming'=>'수강 신청은 추후 제공됩니다.'],
        'vi' => ['headline'=>'Cách tốt hơn để học, dạy và phát triển.','intro'=>'Khám phá các trải nghiệm học tập thực tế và bài học có cấu trúc.','courses'=>'Khám phá khóa học','search'=>'Tìm khóa học','featured'=>'Lộ trình nổi bật','view'=>'Xem khóa học','back'=>'Quay lại khóa học','topic'=>'Bạn sẽ học được','empty'=>'Không tìm thấy khóa học phù hợp.','clear'=>'Xóa tìm kiếm','preview'=>'Xem trước khóa học','coming'=>'Đăng ký khóa học sẽ có trong phiên bản tiếp theo.'],
    ];

    public static function language(string $locale): string
    {
        return isset(self::LANGUAGES[$locale]) ? $locale : 'en';
    }

    public static function label(string $key, string $locale): string
    {
        $locale = self::language($locale);
        return self::LABELS[$locale][$key] ?? self::LABELS['en'][$key] ?? $key;
    }

    public static function url(string $locale, string $path = ''): string
    {
        $prefix = $locale === 'en' ? '' : '/'.$locale;
        $path = trim($path, '/');
        return url($prefix.($path === '' ? '/' : '/'.$path));
    }
}

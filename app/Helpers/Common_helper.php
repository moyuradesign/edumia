<?php

namespace App\Helpers;

final class Common_helper
{
    public const LANGUAGES = [
        'en' => 'English', 'zh' => '中文', 'fr' => 'Français', 'de' => 'Deutsch',
        'ja' => '日本語', 'km' => 'ខ្មែរ', 'ko' => '한국어', 'vi' => 'Tiếng Việt',
    ];

    private const MESSAGES = [
        'en' => ['nav_courses'=>'Explore courses','eyebrow'=>'Learn with confidence','headline'=>'A better way to learn, teach and grow.','intro'=>'Discover practical learning experiences, structured lessons and a supportive space for every learner.','search'=>'Search courses','all_courses'=>'Browse courses','featured'=>'Featured learning paths','featured_intro'=>'Start with a course that matches your next goal.','details'=>'View course','back'=>'Back to courses','lessons'=>'Lessons','level'=>'Level','duration'=>'Duration','curriculum'=>'What you will learn','preview'=>'Preview catalogue','no_results'=>'No courses match your search.','reset'=>'Clear search','about'=>'Learning designed for everyone','about_copy'=>'Edumia brings courses, learners and instructors together in one thoughtful experience.','rights'=>'All rights reserved.','light'=>'Light mode','dark'=>'Dark mode','language'=>'Language','search_placeholder'=>'What would you like to learn?','available'=>'Course preview','coming'=>'Enrollment will be available in a later milestone.'],
        'zh' => ['nav_courses'=>'探索课程','eyebrow'=>'自信学习','headline'=>'更好的学习、教学与成长方式。','intro'=>'探索实用的学习体验、系统课程与支持每位学习者的空间。','search'=>'搜索课程','all_courses'=>'浏览课程','featured'=>'精选学习课程','featured_intro'=>'从符合您目标的课程开始。','details'=>'查看课程','back'=>'返回课程','lessons'=>'课时','level'=>'难度','duration'=>'时长','curriculum'=>'您将学到','preview'=>'课程预览','no_results'=>'没有找到相关课程。','reset'=>'清除搜索','about'=>'为每个人设计的学习','about_copy'=>'Edumia 将课程、学习者与讲师连接在一起。','rights'=>'版权所有。','light'=>'浅色模式','dark'=>'深色模式','language'=>'语言','search_placeholder'=>'您想学习什么？','available'=>'课程预览','coming'=>'报名功能将在后续版本提供。'],
        'fr' => ['nav_courses'=>'Explorer les cours','eyebrow'=>'Apprenez avec confiance','headline'=>'Une meilleure façon d’apprendre, d’enseigner et de progresser.','intro'=>'Découvrez des parcours pratiques et des leçons structurées pour tous.','search'=>'Rechercher','all_courses'=>'Voir les cours','featured'=>'Parcours à découvrir','featured_intro'=>'Commencez par un cours adapté à vos objectifs.','details'=>'Voir le cours','back'=>'Retour aux cours','lessons'=>'Leçons','level'=>'Niveau','duration'=>'Durée','curriculum'=>'Ce que vous apprendrez','preview'=>'Catalogue de démonstration','no_results'=>'Aucun cours trouvé.','reset'=>'Effacer','about'=>'Apprendre pour tous','about_copy'=>'Edumia réunit les apprenants, les enseignants et les cours.','rights'=>'Tous droits réservés.','light'=>'Mode clair','dark'=>'Mode sombre','language'=>'Langue','search_placeholder'=>'Que souhaitez-vous apprendre ?','available'=>'Aperçu du cours','coming'=>'Les inscriptions seront disponibles ultérieurement.'],
        'de' => ['nav_courses'=>'Kurse entdecken','eyebrow'=>'Mit Vertrauen lernen','headline'=>'Besser lernen, lehren und wachsen.','intro'=>'Entdecken Sie praxisnahe Lernangebote und strukturierte Lektionen.','search'=>'Kurse suchen','all_courses'=>'Kurse ansehen','featured'=>'Empfohlene Lernwege','featured_intro'=>'Beginnen Sie mit einem Kurs für Ihr nächstes Ziel.','details'=>'Kurs ansehen','back'=>'Zurück zu den Kursen','lessons'=>'Lektionen','level'=>'Niveau','duration'=>'Dauer','curriculum'=>'Das lernen Sie','preview'=>'Kursvorschau','no_results'=>'Keine passenden Kurse gefunden.','reset'=>'Suche löschen','about'=>'Lernen für alle','about_copy'=>'Edumia verbindet Kurse, Lernende und Lehrkräfte.','rights'=>'Alle Rechte vorbehalten.','light'=>'Helles Design','dark'=>'Dunkles Design','language'=>'Sprache','search_placeholder'=>'Was möchten Sie lernen?','available'=>'Kursvorschau','coming'=>'Anmeldungen folgen in einer späteren Version.'],
        'ja' => ['nav_courses'=>'コースを探す','eyebrow'=>'安心して学ぼう','headline'=>'学び、教え、成長するための新しい方法。','intro'=>'実践的な学習体験と体系的なレッスンを見つけましょう。','search'=>'コースを検索','all_courses'=>'コース一覧','featured'=>'おすすめの学習コース','featured_intro'=>'目標に合ったコースから始めましょう。','details'=>'コースを見る','back'=>'コースに戻る','lessons'=>'レッスン','level'=>'レベル','duration'=>'期間','curriculum'=>'学べる内容','preview'=>'コースプレビュー','no_results'=>'コースが見つかりません。','reset'=>'検索をクリア','about'=>'すべての人に学びを','about_copy'=>'Edumia は学習者と講師とコースをつなぎます。','rights'=>'無断転載を禁じます。','light'=>'ライトモード','dark'=>'ダークモード','language'=>'言語','search_placeholder'=>'何を学びたいですか？','available'=>'コースプレビュー','coming'=>'受講登録は今後の更新で利用可能になります。'],
        'km' => ['nav_courses'=>'ស្វែងរកវគ្គសិក្សា','eyebrow'=>'រៀនដោយទំនុកចិត្ត','headline'=>'វិធីប្រសើរជាងមុនក្នុងការរៀន បង្រៀន និងរីកចម្រើន។','intro'=>'ស្វែងយល់ពីបទពិសោធន៍សិក្សាជាក់ស្តែង និងមេរៀនដែលមានរចនាសម្ព័ន្ធ។','search'=>'ស្វែងរកវគ្គសិក្សា','all_courses'=>'មើលវគ្គសិក្សា','featured'=>'វគ្គសិក្សាពិសេស','featured_intro'=>'ចាប់ផ្តើមជាមួយវគ្គសិក្សាដែលសមនឹងគោលដៅរបស់អ្នក។','details'=>'មើលវគ្គសិក្សា','back'=>'ត្រឡប់ទៅវគ្គសិក្សា','lessons'=>'មេរៀន','level'=>'កម្រិត','duration'=>'រយៈពេល','curriculum'=>'អ្វីដែលអ្នកនឹងរៀន','preview'=>'ការមើលវគ្គសិក្សាជាមុន','no_results'=>'រកមិនឃើញវគ្គសិក្សាទេ។','reset'=>'សម្អាតការស្វែងរក','about'=>'ការសិក្សាសម្រាប់មនុស្សគ្រប់គ្នា','about_copy'=>'Edumia ភ្ជាប់អ្នកសិក្សា គ្រូបង្រៀន និងវគ្គសិក្សា។','rights'=>'រក្សាសិទ្ធិគ្រប់យ៉ាង។','light'=>'របៀបភ្លឺ','dark'=>'របៀបងងឹត','language'=>'ភាសា','search_placeholder'=>'តើអ្នកចង់រៀនអ្វី?','available'=>'ការមើលវគ្គសិក្សា','coming'=>'ការចុះឈ្មោះនឹងមាននៅដំណាក់កាលបន្ទាប់។'],
        'ko' => ['nav_courses'=>'강좌 둘러보기','eyebrow'=>'자신 있게 배우세요','headline'=>'배우고 가르치며 성장하는 더 좋은 방법.','intro'=>'체계적인 수업과 실용적인 학습 경험을 만나보세요.','search'=>'강좌 검색','all_courses'=>'모든 강좌','featured'=>'추천 학습 과정','featured_intro'=>'목표에 맞는 강좌로 시작하세요.','details'=>'강좌 보기','back'=>'강좌로 돌아가기','lessons'=>'수업','level'=>'수준','duration'=>'기간','curriculum'=>'배울 내용','preview'=>'강좌 미리보기','no_results'=>'검색 결과가 없습니다.','reset'=>'검색 초기화','about'=>'모두를 위한 학습','about_copy'=>'Edumia는 학습자, 강사, 강좌를 연결합니다.','rights'=>'모든 권리 보유.','light'=>'라이트 모드','dark'=>'다크 모드','language'=>'언어','search_placeholder'=>'무엇을 배우고 싶으신가요?','available'=>'강좌 미리보기','coming'=>'수강 신청은 추후 제공됩니다.'],
        'vi' => ['nav_courses'=>'Khám phá khóa học','eyebrow'=>'Học tập tự tin','headline'=>'Cách tốt hơn để học, dạy và phát triển.','intro'=>'Khám phá các trải nghiệm học tập thực tế và bài học có cấu trúc.','search'=>'Tìm khóa học','all_courses'=>'Xem khóa học','featured'=>'Lộ trình nổi bật','featured_intro'=>'Bắt đầu với khóa học phù hợp mục tiêu của bạn.','details'=>'Xem khóa học','back'=>'Quay lại khóa học','lessons'=>'Bài học','level'=>'Cấp độ','duration'=>'Thời lượng','curriculum'=>'Bạn sẽ học được','preview'=>'Xem trước khóa học','no_results'=>'Không tìm thấy khóa học phù hợp.','reset'=>'Xóa tìm kiếm','about'=>'Học tập dành cho mọi người','about_copy'=>'Edumia kết nối người học, giảng viên và các khóa học.','rights'=>'Đã đăng ký bản quyền.','light'=>'Chế độ sáng','dark'=>'Chế độ tối','language'=>'Ngôn ngữ','search_placeholder'=>'Bạn muốn học gì?','available'=>'Xem trước khóa học','coming'=>'Đăng ký khóa học sẽ có trong phiên bản tiếp theo.'],
    ];

    public static function language(string $locale): string
    {
        return array_key_exists($locale, self::LANGUAGES) ? $locale : 'en';
    }

    public static function label(string $key, string $locale): string
    {
        $locale = self::language($locale);
        return self::MESSAGES[$locale][$key] ?? self::MESSAGES['en'][$key] ?? $key;
    }

    public static function url(string $locale, string $path = ''): string
    {
        $prefix = $locale === 'en' ? '' : '/'.$locale;
        $path = trim($path, '/');
        return url($prefix.($path === '' ? '/' : '/'.$path));
    }
}

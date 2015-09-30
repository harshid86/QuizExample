<?php

define('OPTIONS_IMAGE_PATH', './img/options/');

class DataModel
{

    /**
     * When creating the model, the configs for database connection creation are needed
     * @param $config
     */
    public function __construct($config)
    {
        //ORM::configure('logging', true);

        ORM::configure("mysql:host={$config['db_host']};dbname={$config['db_name']};port={$config['db_port']};charset=utf8");
        ORM::configure('username', $config['db_user']);
        ORM::configure('password', $config['db_pass']);
    }

    public function loadSettings()
    {
        return Model::factory('Settings')->find_one();
    }

    public function saveSettings($roundPause, $timeLimit, $choicePause, $scorePerWin, $currencySymbol)
    {
        $settings = $this->loadSettings();

        $settings->RoundPauseTime = $roundPause;
        $settings->QuestionTimeLimit = $timeLimit;
        $settings->ChoicePauseTime = $choicePause;
        $settings->ScorePerWin = $scorePerWin;
        $settings->CurrencySymbol = $currencySymbol;

        return $settings->save();
    }

    public function resetQuiz()
    {
        $quiz = Model::factory('Quiz')->findOne();

        if ($quiz !== false) {
            return $quiz->reset();
        }

        return false;
    }

    public function checkQuiz()
    {
        $quiz = Model::factory('Quiz')->findOne();

        if ($quiz !== false) {
            return $quiz->checkIfInProgress();
        }

        return false;
    }
     
    public function rounds()
    {
        return Rounds::Instance();
    }

    public function questions()
    {
        return Questions::Instance();
    }

    public function options()
    {
        return Options::Instance();
    }

    public function teams()
    {
        return Teams::Instance();
    }

    public function choices()
    {
        return Choices::Instance();
    }

}

class Models
{
    private static $instance;
    
    public static function Instance()
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }
        
        return self::$instance;
    }

    protected function _createEntity($name, array $data)
    {
        $entity = Model::factory($name)->create($data);

        return ($entity->save()) ? $entity->id() : false;
    }

    protected function _moveEntityOrder($entityName, $parentId, $entity, $moveDown = true)
    {
        ORM::get_db()->beginTransaction();

        $currentOrder = $entity->Order;

        if (!$moveDown && $currentOrder == 1) {
            return false;
        }

        $newOrder = ($moveDown) ? ($currentOrder + 1) : ($currentOrder - 1);

        $entity->Order = 0;
        $entity->save();

        $entitySwap = ($parentId === null)
            ? Model::factory($entityName)->filter('hasOrder', $newOrder)->find_one()
            : Model::factory($entityName)->filter('hasOrder', $parentId, $newOrder)->find_one();

        if ($entitySwap !== false) {
            $entitySwap->Order = $currentOrder;
            $entitySwap->save();
        } else {
            ORM::get_db()->rollback();
            return false;
        }

        $entity->Order = $newOrder;
        $entity->save();

        return ORM::get_db()->commit();
    }

    protected function _resequenceEntityOrder($entityName, $parentId, $order)
    {
        ORM::get_db()->beginTransaction();

        $entities = ($parentId === null)
            ? Model::factory($entityName)->filter('orderAfter', $order)->findMany()
            : Model::factory($entityName)->filter('orderAfter', $parentId, $order)->findMany();

        foreach($entities as $e) {
            $e->Order = $order;
            $e->save();

            $order += 1;
        }

        return ORM::get_db()->commit();
    }
}

class Rounds extends Models
{
    public function loadAll()
    {
        return Model::factory('Round')->order_by_asc('Order')->find_many();
    }

    public function count()
    {
        return Model::factory('Round')->count();
    }

    public function loadRound($id)
    {
        return Model::factory('Round')->find_one($id);
    }

    public function loadRoundProgress($roundId)
    {
        return Model::factory('RoundProgress')->filter('hasRound', $roundId)->findOne();
    }

    public function createRoundProgress($roundId)
    {
        $progress = Model::factory('RoundProgress')->create([
                        "RoundId" => $roundId,
                        "Started" => true,
                        "Completed" => false
                    ]);

        return $progress->save();
    }

    public function createRound($name)
    {
        $order = $this->count() + 1;

        return $this->_createEntity('Round', array(
            'Name' => $name,
            'Order' => $order
        ));
    }

    public function updateRound($roundId, $name)
    {
        $round = $this->loadRound($roundId);

        if ($round !== false) {
            $round->Name = $name;
            return $round->save();
        }

        return false;
    }

    public function deleteRound($roundId)
    {
        $round = $this->loadRound($roundId);

        if ($round !== false) {
            $order = $round->Order;

            $this->_resequenceEntityOrder('Round', null, $order);

            return $round->delete();
        }

        return false;
    }

    public function moveRoundDown($roundId)
    {
        $round = $this->loadRound($roundId);

        if ($round !== false) {

            return $this->_moveEntityOrder('Round', null, $round, true);
        }

        return false;
    }

    public function moveRoundUp($roundId)
    {
        $round = $this->loadRound($roundId);

        if ($round !== false) {

            return $this->_moveEntityOrder('Round', null, $round, false);
        }

        return false;
    }

    public function revertRound($roundId)
    {
        $round = $this->loadRound($roundId);

        if ($round !== false) {
            
            return $round->revert();
        }

        return false;
    }

    public function completeRound($roundId)
    {
        $progress = $this->loadRoundProgress($roundId);

        if ($progress !== false) {

            return $progress->complete();
        }

        return false;
    }

    public function startRound($roundId)
    {
        $progress = $this->loadRoundProgress($roundId);

        return ($progress !== false)
            ? $progress->start()
            : $this->createRoundProgress($roundId);
    }
}

class Questions extends Models
{
    private function _all($roundId)
    {
        return Model::factory('Question')
                ->filter('hasRound', $roundId)
                ->order_by_asc('Order');
    }

    public function loadAll($roundId)
    {
        return $this->_all($roundId)->findMany();
    }

    public function count($roundId)
    {
        return $this->_all($roundId)->count();
    }

    public function loadQuestion($id)
    {
        return Model::factory('Question')->find_one($id);
    }

    public function loadQuestionProgress($questionId)
    {
        return Model::factory('QuestionProgress')->filter('hasQuestion', $questionId)->findOne();
    }

    public function createQuestionProgress($questionId)
    {
        $progress = Model::factory('QuestionProgress')->create([
                    "QuestionId" => $questionId,
                    "Started" => true
                ]);

        return $progress->save();
    }

    public function createQuestion($question, $roundId)
    {
        $order = $this->count($roundId) + 1;

        return $this->_createEntity('Question', array(
            'QuestionText' => $question,
            'RoundId' => $roundId,
            'Order' => $order
        ));
    }

    public function updateQuestion($questionId, $questionText)
    {
        $question = $this->loadQuestion($questionId);

        if ($question !== false) {
            $question->QuestionText = $questionText;

            return $question->save();
        }

        return false;
    }

    public function deleteQuestion($questionId)
    {
        $question = $this->loadQuestion($questionId);

        if ($question !== false) {
            $order = $question->Order;

            $this->_resequenceEntityOrder('Question', $question->round()->id(), $order);

            return $question->delete();
        }

        return false;
    }

    public function moveQuestionDown($questionId)
    {
        $question = $this->loadQuestion($questionId);

        if ($question !== false) {

            return $this->_moveEntityOrder('Question', $question->RoundId, $question, true);
        }

        return false;
    }

    public function moveQuestionUp($questionId)
    {
        $question = $this->loadQuestion($questionId);

        if ($question !== false) {

            return $this->_moveEntityOrder('Question', $question->RoundId, $question, false);
        }

        return false;
    }
    
    public function revertQuestion($questionId)
    {
        $question = $this->loadQuestion($questionId);

        if ($question !== false) {

            return $question->revert();
        }

        return false;
    }

    public function startQuestion($questionId)
    {
        $progress = $this->loadQuestionProgress($questionId);

        return ($progress !== false)
            ? $progress->start()
            : $this->createQuestionProgress($questionId);
    }

    public function canStopQuestion($questionId)
    {
        $question = $this->loadQuestion($questionId);
        $quiz = $this->loadOpenGame();

        if ($question !== false && $quiz !== false) {
            echo (count($quiz->teams()) == count($question->choices()));
        }
    }

    public function completeQuestion($questionId)
    {
        $progress = $this->loadQuestionProgress($questionId);

        if ($progress !== false) {

            return $progress->complete();
        }

        return false;
    }
}

class Options extends Models
{
    private function _all($questionId)
    {
        return Model::factory('Option')
                ->filter('hasQuestion', $questionId);
    }

    public function loadAll($questionId)
    {
        return $this->_all($questionId)->findMany();
    }

    public function count($questionId)
    {
        return $this->_all($questionId)->count();
    }

    public function loadOption($id)
    {
        return Model::factory('Option')->find_one($id);
    }
    
    public function createOption($optionText, $score, $questionId, $isAnswer = false)
    {
        $order = $this->count($questionId) + 1;

        $option = $this->_createEntity('Option', array(
            'OptionText' => $optionText,
            'OptionScore' => $score,
            'QuestionId' => $questionId,
            'Order' => $order
        ));

        if ($isAnswer) {
            $this->makeOptionCorrectAnswer($option);
        }

        return $option;
    }

    public function updateOption($optionId, $optionText, $score, $isAnswer = false)
    {
        $option = $this->loadOption($optionId);

        if ($option !== false) {
            $option->OptionText = $optionText;
            $option->OptionScore = $score;

            if ($isAnswer) {
                $option->makeCorrectAnswer();
            }

            return $option->save();
        }

        return false;
    }

    public function uploadOptionImage($optionId, $imageTempPath, $ext)
    {
        $option = $this->loadOption($optionId);

        if ($option !== false) {

            $optionId = $option->id();

            $question = $option->question();
            $questionId = $question->id();

            $round = $question->round();
            $roundId = $round->id();

            $random = rand(0, 100000);

            $name = "r{$roundId}q{$questionId}o{$optionId}-{$random}.{$ext}";

            $imagePath = OPTIONS_IMAGE_PATH . $name;

            echo $imagePath;

            if (file_exists($imagePath)) {
                chmod($imagePath, 0755);
                echo unlink($imagePath);
            }

            if (move_uploaded_file($imageTempPath, $imagePath)) {
                $option->OptionImage = $name;

                return $option->save();
            }
        }

        return false;
    }

    public function makeOptionCorrectAnswer($optionId)
    {
        $option = $this->loadOption($optionId);

        if ($option !== false) {

            return $option->makeCorrectAnswer();
        }

        return false;
    }

    public function deleteOption($optionId)
    {
        $option = $this->loadOption($optionId);

        if ($option !== false) {
            $order = $option->Order;

            $this->_resequenceEntityOrder('Option', $option->question()->id(), $order);

            return $option->delete();
        }

        return false;
    }

    public function moveOptionDown($optionId)
    {
        $option = $this->loadOption($optionId);

        if ($option !== false) {

            return $this->_moveEntityOrder('Option', $option->QuestionId, $option, true);
        }

        return false;
    }

    public function moveOptionUp($optionId)
    {
        $option = $this->loadOption($optionId);

        if ($option !== false) {

            return $this->_moveEntityOrder('Option', $option->QuestionId, $option, false);
        }

        return false;
    }

}

class Teams extends Models
{
    public function loadAll()
    {
        return Model::factory('Team')->find_many();
    }

    public function loadTeam($id)
    {
        return Model::factory('Team')->find_one($id);
    }

    public function createTeam($name)
    {
        return $this->_createEntity('Team', array(
            'Name' => $name
        ));
    }

    public function updateTeam($teamId, $name)
    {
        $team = $this->loadTeam($teamId);

        if ($team !== false) {
            $team->Name = $name;

            return $team->save();
        }

        return false;
    }

    public function deleteTeam($teamId)
    {
        $team = $this->loadTeam($teamId);

        if ($team !== false) {
            return $team->delete();
        }

        return false;
    }
}

class Choices extends Models
{
    public function loadAll()
    {
        return Model::factory('Choice')->find_many();
    }

    public function createChoice($teamId, $optionId)
    {
        $option = Model::factory('Option')->findOne($optionId);

        if ($option !== false) {
            $choice = Model::factory('Choice')
                        ->filter('choiceExists', $option->question()->id(), $teamId)
                        ->findOne();

            if ($choice == false) {
                return $this->_createEntity('Choice', array(
                    'TeamId' => $teamId,
                    'QuestionId' => $option->question()->id(),
                    'OptionId' => $optionId
                ));
            }
            else {

                $choice->OptionId = $optionId;

                return $choice->save();
            }
        }

        return false;
    }

    public function createEmptyChoice($teamId, $questionId)
    {
        $choice = Model::factory('Choice')
                    ->filter('choiceExists', $questionId, $teamId)
                    ->findOne();

        if ($choice !== false) {
            $choice->delete();
        }

        return $this->_createEntity('Choice', array(
                'TeamId' => $teamId,
                'QuestionId' => $questionId
            ));
    }
}

class Settings extends Model
{
    public static $_table = 'Settings';
}


class Quiz extends Model
{
    public static $_table = 'Quiz';

    public function teams()
    {
        return Model::factory('Team')->find_many();
    }

    public function rounds()
    {
        return Model::factory('Round')->find_many();
    }

    public function choices()
    {
        return Model::factory('Choice')->find_many();
    }

    public function roundProgress()
    {
        return Model::factory('RoundProgress')->find_many();
    }

    public function questionProgress()
    {
        return Model::factory('QuestionProgress')->find_many();
    }

    public function delete()
    {
        
        $this->reset();
        
        return parent::delete();
    }

    public function reset()
    {
        foreach ($this->choices() as $choice) {
            $choice->delete();
        }

        foreach ($this->roundProgress() as $progress) {
            $progress->delete();
        }

        foreach ($this->questionProgress() as $progress) {
            $progress->delete();
        }
    }

    public function checkIfInProgress()
    {
        return (Model::factory('RoundProgress')->count() > 0);
    }

}


class Team extends Model
{
    public static $_table = 'Team';

    public function choices()
    {
        return $this->has_many('Choice', 'TeamId', 'id')->find_many();
    }

    public function delete()
    {
        foreach ($this->choices() as $choice) {
            $choice->delete();
        }

        return parent::delete();
    }
}

class Round extends Model
{
    public static $_table = 'Round';

    public function questions()
    {
        return $this->has_many('Question', 'RoundId', 'id')->order_by_asc('Order')->find_many();
    }

    public function progress()
    {
        return $this->has_many('RoundProgress', 'RoundId', 'id')->find_many();
    }

    public function deleteProgress($delete = false)
    {
        foreach ($this->progress() as $progress) {
            if ($delete) {
                $progress->delete();
            } else {
                $progress->revert();
            }
        }

        foreach ($this->questions() as $question) {
            $question->deleteProgress($delete);
        }
    }

    public function delete()
    {
        foreach ($this->questions() as $question) {
            $question->delete();
        }

        $this->deleteProgress();

        return parent::delete();
    }

    public function revert()
    {
        return $this->deleteProgress();
    }

    public static function hasOrder($orm, $order)
    {
        return $orm->where('Order', $order);
    }

    public static function orderAfter($orm, $order)
    {
        return $orm->where_gt('Order', $order);
    }
}

class Question extends Model
{
    public static $_table = 'Question';

    public function round()
    {
        return $this->belongs_to('Round', 'RoundId', 'id')->find_one();
    }

    public function options()
    {
        return $this->has_many('Option', 'QuestionId', 'id')->order_by_asc('Order')->find_many();
    }

    public function progress()
    {
        return $this->has_many('QuestionProgress', 'QuestionId', 'id')->find_many();
    }

    public function choices()
    {
        return $this->has_many('Choice', 'QuestionId', 'id')->find_many();
    }

    public function answer()
    {
        return $this->has_one('Option', 'AnswerOptionId', 'id')->find_one();
    }

    public function deleteProgress($delete = false)
    {
        foreach ($this->progress() as $progress) {
            if ($delete) {
                $progress->delete();
            } else {
                $progress->revert();
            }
        }

        foreach ($this->choices() as $choice) {
            $choice->delete();
        }
    }

    public function delete()
    {
        foreach ($this->options() as $option) {
            $option->delete();
        }

        $this->deleteProgress();

        return parent::delete();
    }

    public function revert()
    {
        return $this->deleteProgress();
    }

    public static function hasRound($orm, $roundId)
    {
        return $orm->where('RoundId', $roundId);
    }

    public static function hasOrder($orm, $roundId, $order)
    {
        return $orm->where('RoundId', $roundId)->where('Order', $order);
    }

    public static function orderAfter($orm, $roundId, $order)
    {
        return $orm->where('RoundId', $roundId)->where_gt('Order', $order);
    }
}

class Option extends Model
{
    public static $_table = 'Option';

    public function question()
    {
        return $this->belongs_to('Question', 'QuestionId', 'id')->find_one();
    }

    public function choices()
    {
        return $this->has_many('Choice', 'OptionId', 'id')->find_many();
    }

    public function isAnswer()
    {
        return ($this->question()->AnswerOptionId == $this->id());
    }

    public function makeCorrectAnswer()
    {
        $question = $this->question();

        if ($question !== false) {
            $question->AnswerOptionId = $this->id();
            return $question->save();
        }

        return false;
    }

    public function delete()
    {
        foreach ($this->choices() as $choice) {
            $choice->delete();
        }

        $deleteFile = OPTIONS_IMAGE_PATH . $this->OptionImage;
        if (is_file($deleteFile)) {
            echo unlink($deleteFile);
        }

        return parent::delete();
    }

    public static function hasQuestion($orm, $questionId)
    {
        return $orm->where('QuestionId', $questionId);
    }

    public static function hasOrder($orm, $questionId, $order)
    {
        return $orm->where('QuestionId', $questionId)->where('Order', $order);
    }

    public static function orderAfter($orm, $questionId, $order)
    {
        return $orm->where('QuestionId', $questionId)->where_gt('Order', $order);
    }
}

class Choice extends Model
{
    public static $_table = 'Choice';

    public function team()
    {
        return $this->belongs_to('Team', 'TeamId', 'id')->find_one();
    }

    public function option()
    {
        return $this->belongs_to('Option', 'OptionId', 'id')->find_one();
    }

    public function question()
    {
        return $this->belongs_to('Question', 'QuestionId', 'id')->find_one();
    }

    public static function choiceExists($orm, $questionId, $teamId)
    {
        return $orm->where('QuestionId', $questionId)->where('TeamId', $teamId);
    }
}

class RoundProgress extends Model
{
    public static $_table = 'RoundProgress';

    public function round()
    {
        return $this->belongs_to('Round', 'RoundId', 'id')->find_one();
    }

    public function complete()
    {
        $this->Started = true;
        $this->Completed = true;

        return $this->save();
    }

    public function start()
    {
        $this->Started = true;
        $this->Completed = false;
        $this->Reverted = false;

        return $this->save();
    }

    public function revert()
    {
        $this->Started = false;
        $this->Completed = false;
        $this->Reverted = true;

        return $this->save();
    }

    public static function hasRound($orm, $roundId)
    {
        return $orm->where('RoundId', $roundId);
    }
}

class QuestionProgress extends Model
{
    public static $_table = 'QuestionProgress';

    public function round()
    {
        return $this->belongs_to('Question', 'QuestionId', 'id')->find_one();
    }

    public function complete()
    {
        $this->Started = true;
        $this->Completed = true;

        return $this->save();
    }

    public function start()
    {
        $this->Started = true;
        $this->Completed = false;
        $this->Reverted = false;

        return $this->save();
    }

    public function revert()
    {
        $this->Started = false;
        $this->Completed = false;
        $this->Reverted = true;

        return $this->save();
    }

    public static function hasQuestion($orm, $questionId)
    {
        return $orm->where('QuestionId', $questionId);
    }
}
<?php

define('JSON_CONT_TYPE', 'application/json;charset=utf-8');

/************************************ THE ROUTES / CONTROLLERS *************************************************/

function entitiesAsArray(array $entities, $manipulate = null)
{
    $array = [];
    foreach ($entities as $entity) {
        if ($manipulate !== null) {
            $manipulate($entity);
        }

        $array[] = $entity->as_array();
    }
    return $array;
}

function getPostParams()
{
    return json_decode(file_get_contents('php://input'), true);
}

$app->get('/', function () use ($app) {
    $app->render('index.twig');
});

$app->get('/host', function () use ($app) {
    $app->render('host.twig');
});

$app->get('/leaderboard', function () use ($app) {
    $app->render('leaderboard.twig', array(
            'isHost' => 1,
            'gameEnd' => 0
        ));
});

$app->get('/leaderboard/player', function () use ($app) {
    $app->render('leaderboard.twig', array(
            'isHost' => 0,
            'gameEnd' => 1
        ));
});

$app->get('/leaderboard/host', function () use ($app) {
    $app->render('leaderboard.twig', array(
            'isHost' => 1,
            'gameEnd' => 1
        ));
});

$app->get('/player', function () use ($app) {
    $app->render('play.twig', array(
            'isHost' => 0,
            'showControls' => 0
        ));
});

$app->get('/host/play', function () use ($app) {
    $app->render('play.twig', array(
            'isHost' => 1,
            'showControls' => 0
        ));
});

$app->get('/host/play/controls', function () use ($app) {
    $app->render('play.twig', array(
            'isHost' => 1,
            'showControls' => 1
        ));
});


$app->group('/admin', function () use ($app, $model) {

    $app->get('/', function () use ($app, $model) {
        $app->render('admin.twig');
    });

    $app->get('/settings', function () use ($app) {
        $app->render('settings.twig');
    });

    $app->get('/teams', function () use ($app) {
        $app->render('teams.twig');
    });

    $app->get('/rounds', function () use ($app) {
        $app->render('rounds.twig');
    });

    $app->get('/round/:round_id', function ($round_id) use ($app) {
        $app->render('rounds.twig');
    });

    $app->get('/round/:round_id/questions', function ($round_id) use ($app) {
        $app->render('questions.twig', array(
            'roundId' => $round_id
        ));
    });

    $app->get('/round/:round_id/question/:question_id', function ($round_id, $question_id) use ($app) {
        $app->render('questions.twig');
    });

    $app->get('/round/:round_id/question/:question_id/options', function ($round_id, $question_id) use ($app) {
        $app->render('options.twig', array(
            'roundId' => $round_id,
            'questionId' => $question_id
        ));
    });

    $app->get('/round/:round_id/question/:question_id/option/:option_id',
        function ($round_id, $question_id, $option_id) use ($app) {
            $app->render('options.twig');
        });
});

$app->group('/ajax', function () use ($app, $model) {

    $app->get('/', function () use ($app, $model) {

    });

    $app->get('/game/check', function () use ($app, $model) {
        echo $model->checkQuiz();
        
    });

    $app->get('/game/reset', function () use ($app, $model) {
        $model->resetQuiz();
    });

    $app->post('/teams/add', function () use ($app, $model) {
        $params = getPostParams();
        $team = $model->teams()->createTeam($params["name"]);

        if ($team !== false) {

            echo $team;
        }
    });

    $app->post('/team/:team_id/edit', function ($team_id) use ($app, $model) {
        $params = getPostParams();
        $model->teams()->updateTeam($team_id, $params["name"]);
    });

    $app->get('/team/:team_id/delete', function ($team_id) use ($app, $model) {
        $model->teams()->deleteTeam($team_id);
    });

    $app->get('/team/:team_id', function ($team_id) use ($app, $model) {

        $team = $model->teams()->loadTeam($team_id);

        if ($team !== false) {
            $app->contentType(JSON_CONT_TYPE);
            echo json_encode($team->as_array());
        }
    });

    $app->get('/teams', function () use ($app, $model) {

        $teams = $model->teams()->loadAll();

        if ($teams !== false) {
            $json = entitiesAsArray($teams);

            $app->contentType(JSON_CONT_TYPE);
            echo json_encode($json);
        }
    });

    $app->get('/settings', function () use ($app, $model) {

        $settings = $model->loadSettings();

        if ($settings !== false) {
            $app->contentType(JSON_CONT_TYPE);
            echo json_encode($settings->as_array());
        }
    });

    $app->post('/settings', function () use ($app, $model) {
        $params = getPostParams();
        $model->saveSettings($params["RoundPauseTime"], $params["QuestionTimeLimit"], $params["AnswerRevealPauseTime"], $params["ChoicePauseTime"], $params["ScorePerWin"], $params["CurrencySymbol"]);
    });

    $app->get('/roundprogress/:round_id', function ($round_id) use ($app, $model) {

        $progress = $model->rounds()->loadRoundProgress($round_id);

        if ($progress !== false) {
            $app->contentType(JSON_CONT_TYPE);
            echo json_encode($progress->as_array());
        }
    });

    $app->get('/roundprogress/:round_id/start', function ($round_id) use ($app, $model) {

        echo $model->rounds()->startRound($round_id);
    });

    $app->get('/roundprogress/:round_id/complete', function ($round_id) use ($app, $model) {

        echo $model->rounds()->completeRound($round_id);
    });

    $app->get('/roundprogress/:round_id/revert', function ($round_id) use ($app, $model) {

        echo $model->rounds()->revertRound($round_id);
    });

    $app->get('/questionprogress/:question_id', function ($question_id) use ($app, $model) {

        $progress = $model->questions()->loadQuestionProgress($question_id);

        if ($progress !== false) {
            $app->contentType(JSON_CONT_TYPE);
            echo json_encode($progress->as_array());
        }
    });

    $app->get('/questionprogress/:question_id/canstop', function ($question_id) use ($app, $model) {

        echo $model->questions()->canStopQuestion($question_id);
    });

    $app->get('/questionprogress/:question_id/start', function ($question_id) use ($app, $model) {

        echo $model->questions()->startQuestion($question_id);
    });

    $app->get('/questionprogress/:question_id/complete', function ($question_id) use ($app, $model) {

        echo $model->questions()->completeQuestion($question_id);
    });

    $app->get('/questionprogress/:question_id/revert', function ($question_id) use ($app, $model) {

        echo $model->questions()->revertQuestion($question_id);
    });

    $app->get('/rounds', function () use ($app, $model) {

        $rounds = $model->rounds()->loadAll();

        if ($rounds !== false) {

            $json = $json = entitiesAsArray($rounds);

            $app->contentType(JSON_CONT_TYPE);
            echo json_encode($json);
        }
    });

    $app->get('/round/:round_id', function ($round_id) use ($app, $model) {

        $round = $model->rounds()->loadRound($round_id);

        if ($round !== false) {
            $app->contentType(JSON_CONT_TYPE);
            echo json_encode($round->as_array());
        }
    });

    $app->get('/round/:round_id/questions', function ($round_id) use ($app, $model) {

        $questions = $model->questions()->loadAll($round_id);

        if ($questions !== false) {
            $json = entitiesAsArray($questions);

            $app->contentType(JSON_CONT_TYPE);
            echo json_encode($json);
        }
    });

    $app->post('/rounds/add', function () use ($app, $model) {
        $params = getPostParams();
        $model->rounds()->createRound($params["name"]);
    });

    $app->post('/round/:round_id/edit', function ($round_id) use ($app, $model) {
        $params = getPostParams();
        $model->rounds()->updateRound($round_id, $params["name"]);
    });

    $app->get('/round/:round_id/delete', function ($round_id) use ($app, $model) {
        $model->rounds()->deleteRound($round_id);
    });

    $app->get('/round/:round_id/moveup', function ($round_id) use ($app, $model) {
        
        $model->rounds()->moveRoundUp($round_id);
    });

    $app->get('/round/:round_id/movedown', function ($round_id) use ($app, $model) {
        
        $model->rounds()->moveRoundDown($round_id);
    });

    $app->get('/questions', function () use ($app, $model) {

        $questions = $model->questions()->loadAll();

        if ($questions !== false) {

            $json = $json = entitiesAsArray($questions);

            $app->contentType(JSON_CONT_TYPE);
            echo json_encode($json);
        }
    });

    $app->get('/question/:question_id', function ($question_id) use ($app, $model) {

        $question = $model->questions()->loadQuestion($question_id);

        if ($question !== false) {
            $app->contentType(JSON_CONT_TYPE);
            echo json_encode($question->as_array());
        }
    });

    $app->get('/question/:question_id/options', function ($question_id) use ($app, $model) {

        $options = $model->options()->loadAll($question_id);

        if ($options !== false) {
            $json = entitiesAsArray($options, function ($entity) {
                $entity->IsAnswer = $entity->isAnswer();
            });

            $app->contentType(JSON_CONT_TYPE);
            echo json_encode($json);
        }
    });

    $app->post('/questions/add', function () use ($app, $model) {
        $params = getPostParams();
        $model->questions()->createQuestion($params["questionText"], $params["roundId"]);
    });

    $app->post('/question/:question_id/edit', function ($question_id) use ($app, $model) {
        $params = getPostParams();
        $model->questions()->updateQuestion($question_id, $params["questionText"]);
    });

    $app->get('/question/:question_id/delete', function ($question_id) use ($app, $model) {
        $model->questions()->deleteQuestion($question_id);
    });

    $app->get('/question/:question_id/moveup', function ($question_id) use ($app, $model) {
        
        $model->questions()->moveQuestionUp($question_id);
    });

    $app->get('/question/:question_id/movedown', function ($question_id) use ($app, $model) {
        
        $model->questions()->moveQuestionDown($question_id);
    });

    $app->get('/option/:option_id', function ($option_id) use ($app, $model) {
        $option = $model->options()->loadOption($option_id);

        if ($option !== false) {
            $app->contentType(JSON_CONT_TYPE);
            echo json_encode($option->as_array());            
        }
    });

    $app->post('/option/:option_id/uploadimage', function ($option_id) use ($app, $model) {
        
        if (!isset($_FILES['file'])) {
            return;
        }

        $file = $_FILES['file'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);

        $model->options()->uploadOptionImage($option_id, $file['tmp_name'], $ext);
    });

    $app->post('/options/add', function () use ($app, $model) {
        $params = getPostParams();
        echo $model->options()->createOption($params["option"], $params["score"], $params["questionId"], $params["isAnswer"]);
    });

    $app->post('/option/:option_id/edit', function ($option_id) use ($app, $model) {
        $params = getPostParams();
        echo $model->updateOption($option_id, $params["option"], $params["score"]);
    });

    $app->get('/option/:option_id/correctanswer', function ($option_id) use ($app, $model) {
        
        $model->options()->makeOptionCorrectAnswer($option_id);
    });

    $app->get('/option/:option_id/delete', function ($option_id) use ($app, $model) {
        $model->options()->deleteOption($option_id);
    });

    $app->get('/option/:option_id/moveup', function ($option_id) use ($app, $model) {
        
        $model->options()->moveOptionUp($option_id);
    });

    $app->get('/option/:option_id/movedown', function ($option_id) use ($app, $model) {
        
        $model->options()->moveOptionDown($option_id);
    });

    $app->post('/choices/add', function () use ($app, $model) {
        $params = getPostParams();
        $model->choices()->createChoice($params["teamId"], $params["optionId"]);
    });

    $app->post('/choices/addempty', function () use ($app, $model) {
        $params = getPostParams();
        $model->choices()->createEmptyChoice($params["teamId"], $params["questionId"]);
    });
});


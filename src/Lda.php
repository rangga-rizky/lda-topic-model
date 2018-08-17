<?php 
namespace TopicModel;

class Lda {
    protected $n_of_topics,$document_topic_counts,$W,$topic_word_counts,$topic_counts,$document_lengths,$alpha,$beta;

    public function modelling($tokenized_data,$n_of_topics,$alpha,$beta){       
      
        $this->n_of_topics = $n_of_topics;
        $this->alpha = $alpha;
        $this->beta = $beta;
        $result = [];
        $distinct_words = [];
        $this->document_lengths = [];
        
        foreach ($tokenized_data as $document) {
            $this->document_lengths[] = sizeof($document["tokens"]);
            foreach ($document["tokens"] as $word) {
                if(!in_array($word, $distinct_words)){
                    $distinct_words[] = $word;
                }
            }
        }  

        $this->W = sizeof($distinct_words);

        //init document_topic_counts
        for ($i=0; $i < sizeof($tokenized_data); $i++) { 
            for ($j=0; $j < $this->n_of_topics; $j++) { 
                $this->document_topic_counts[$i][$j] = 0;
            }
        }
              

        for ($i=0; $i < $this->n_of_topics; $i++) { 
            $this->topic_counts[$i] = 0;
        }
        $total_lenght = sizeof($tokenized_data);

        //inisialisasi tiap kata dengan random topik
        $document_topics = [];
        foreach ($tokenized_data as $document) {
            $sentence_topic = array();
            foreach ($document["tokens"] as $word) {
                $sentence_topic[] = mt_rand( 0 , $this->n_of_topics-1 );
            }
            $document_topics[] = $sentence_topic;
        }

     


        #looping per dokumen
        for ($d=0; $d < $total_lenght; $d++) { 
             #looping pada setiap kata  yang ada
            for ($w=0; $w < sizeof($tokenized_data[$d]["tokens"]) ; $w++) { 
                $this->document_topic_counts[$d][$document_topics[$d][$w]]++;
                if(!isset($this->topic_word_counts[$document_topics[$d][$w]][$tokenized_data[$d]["tokens"][$w]])){
                    $this->topic_word_counts[$document_topics[$d][$w]][$tokenized_data[$d]["tokens"][$w]] = 0;
                }                
                if(isset($this->topic_word_counts[$document_topics[$d][$w]][$tokenized_data[$d]["tokens"][$w]])){
                    $this->topic_word_counts[$document_topics[$d][$w]][$tokenized_data[$d]["tokens"][$w]]++;
                }
                $this->topic_counts[$document_topics[$d][$w]]++; 
            }
        }

        for ($iterasi=0; $iterasi < 1000; $iterasi++) { 
            for ($d=0; $d < $total_lenght; $d++) { 
                for ($w=0; $w < sizeof($tokenized_data[$d]["tokens"]) ; $w++) { 
                    $this->document_topic_counts[$d][$document_topics[$d][$w]]--;
                    $this->topic_word_counts[$document_topics[$d][$w]][$tokenized_data[$d]["tokens"][$w]]--;
                    $this->topic_counts[$document_topics[$d][$w]]--;
                    $this->document_lengths[$d]--;
                    
                    $new_topic = $this->choose_new_topic($d, $tokenized_data[$d]["tokens"][$w]);
                    $document_topics[$d][$w] = $new_topic;
                    
                    
                    $this->document_topic_counts[$d][$document_topics[$d][$w]]++;
                    $this->topic_word_counts[$document_topics[$d][$w]][$tokenized_data[$d]["tokens"][$w]]++;
                    $this->topic_counts[$document_topics[$d][$w]]++;
                    $this->document_lengths[$d]++;

                }
            }
        }

        for ($i=0; $i < sizeof($this->topic_word_counts); $i++) { 
            arsort($this->topic_word_counts[$i]);           
            $topics[$i]["words"] = array_slice($this->topic_word_counts[$i],0, 10);
        }

        for ($i=0; $i < sizeof($document_topics) ; $i++) { 
            $t = array_count_values($document_topics[$i]);
            $topics[key($t)]["complaint_ids"][] = $tokenized_data[$i]["id"];
        }

        return $topics;
    }


    private function p_topic_given_document($topic, $d, $alpha=0.1){
        return (($this->document_topic_counts[$d][$topic] + $alpha) /
            ($this->document_lengths[$d] + $this->n_of_topics * $alpha));
    }

    private function p_word_given_topic($word, $topic, $beta=0.1){
        if(!isset($this->topic_word_counts[$topic][$word])){
            $this->topic_word_counts[$topic][$word] = 0;
        }
        
        return (($this->topic_word_counts[$topic][$word] + $beta) /
        ($this->topic_counts[$topic] + $this->W * $beta));
    }

    private function topic_weight($d, $word, $K_){
        return $this->p_word_given_topic($word, $K_,$this->beta) * $this->p_topic_given_document($K_, $d,$this->alpha);
    }

    
    private function sample_from($weights){
        $total = array_sum($weights);
        $rnd =  ((float) mt_rand( 0 , 9 )/10) * $total;
        for ($i=0; $i < sizeof($weights); $i++) { 
            $rnd = (float) $rnd - $weights[$i];
            if($rnd <=0){
                return $i;
            }
        }
    }

    private function choose_new_topic($d, $word){
        $topic_weights = [];
        for ($i=0; $i < $this->n_of_topics; $i++) { 
            $topic_weights[] = $this->topic_weight($d, $word, $i);
        }
        return $this->sample_from($topic_weights);
    }

}